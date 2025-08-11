<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Owner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'user_id',
    ];

    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    // Méthode de compatibilité pour récupérer la première propriété
    public function property()
    {
        return $this->properties()->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remittance()
    {
        return $this->hasMany(Remittance::class);
    }

    public function remittancePartials()
    {
        return $this->hasMany(RemittancePartial::class);
    }

    // Relation factice pour le tenant scoping Filament
    // Les owners peuvent avoir des propriétés dans différentes agences
    public function agencys(): BelongsTo
    {
        // Retourne une relation vide - les owners sont gérés globalement
        return $this->belongsTo(Agency::class, 'id', 'id')->whereRaw('1 = 0');
    }

    /**
     * Trouve l'utilisateur associé à ce propriétaire (avec fallback)
     */
    public function getAssociatedUser(): ?User
    {
        // Méthode principale : relation directe user_id
        if ($this->user_id && $this->user) {
            return $this->user;
        }

        // Fallback : recherche par email
        if ($this->email) {
            $user = User::where('email', $this->email)->first();
            if ($user) {
                return $user;
            }
        }

        // Fallback : recherche par nom (correspondance partielle)
        if ($this->name) {
            $user = User::where('name', 'like', '%' . trim($this->name) . '%')->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Vérifie si ce propriétaire a un utilisateur associé valide
     */
    public function hasValidUserAssociation(): bool
    {
        return $this->getAssociatedUser() !== null;
    }

    /**
     * Obtient l'agence de ce propriétaire via sa propriété
     */
    public function getAgency(): ?Agency
    {
        return $this->property?->agency ?? null;
    }

    /**
     * Vérifie la cohérence des données de ce propriétaire
     */
    public function validateDataConsistency(): array
    {
        $issues = [];

        // Vérifier la relation user
        if (!$this->hasValidUserAssociation()) {
            $issues[] = 'No valid user association found';
        }

        // Vérifier la propriété
        if (!$this->property) {
            $issues[] = 'No property associated';
        }

        // Vérifier la cohérence agence-utilisateur
        $user = $this->getAssociatedUser();
        $agency = $this->getAgency();

        if ($user && $agency) {
            $userAgencyIds = $user->agencys->pluck('id')->toArray();
            if (!in_array($agency->id, $userAgencyIds)) {
                $issues[] = 'User agency mismatch with property agency';
            }
        }

        return $issues;
    }

    /**
     * Checks if the owner has a valid property association for remittance creation
     * 
     * @return bool
     */
    public function hasValidPropertyForRemittance(): bool
    {
        // Check if owner has at least one property
        if ($this->properties()->count() === 0) {
            return false;
        }

        // Check if the primary property exists and is valid
        $property = $this->property();
        if (!$property) {
            return false;
        }

        // Verify property has required fields for remittance calculations
        if (!$property->id || !$property->agency_id) {
            return false;
        }

        // Check if property has associated flats (needed for payment calculations)
        if ($property->flats()->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * Validates if the owner can create a remittance
     * 
     * @return bool
     */
    public function canCreateRemittance(): bool
    {
        // Must have valid property association
        if (!$this->hasValidPropertyForRemittance()) {
            return false;
        }

        // Must have valid user association for access control
        if (!$this->hasValidUserAssociation()) {
            return false;
        }

        // Check data consistency
        $consistencyIssues = $this->validateDataConsistency();
        if (!empty($consistencyIssues)) {
            return false;
        }

        // Verify property has commission settings for calculations
        $property = $this->property();
        if (!$property->commission_value || !$property->commission_unit) {
            return false;
        }

        return true;
    }

    /**
     * Returns specific validation errors preventing remittance creation
     * 
     * @return array Array of validation error messages
     */
    public function getRemittanceValidationErrors(): array
    {
        $errors = [];

        // Check property association
        if ($this->properties()->count() === 0) {
            $errors[] = 'Owner has no associated properties';
        } else {
            $property = $this->property();
            if (!$property) {
                $errors[] = 'Primary property not found';
            } else {
                // Check property validity
                if (!$property->id) {
                    $errors[] = 'Property ID is missing';
                }

                if (!$property->agency_id) {
                    $errors[] = 'Property is not associated with an agency';
                }

                // Check if property has flats
                if ($property->flats()->count() === 0) {
                    $errors[] = 'Property has no associated flats for payment calculations';
                }

                // Check commission settings
                if (!$property->commission_value) {
                    $errors[] = 'Property commission value is not set';
                }

                if (!$property->commission_unit) {
                    $errors[] = 'Property commission unit is not configured';
                }
            }
        }

        // Check user association
        if (!$this->hasValidUserAssociation()) {
            $errors[] = 'Owner has no valid user association for access control';
        }

        // Include data consistency issues
        $consistencyIssues = $this->validateDataConsistency();
        foreach ($consistencyIssues as $issue) {
            $errors[] = "Data consistency issue: {$issue}";
        }

        // Check for existing incomplete remittances
        $incompleteRemittances = $this->remittance()
            ->whereNull('remittance_date')
            ->orWhere('status', '!=', 'completed')
            ->count();

        if ($incompleteRemittances > 0) {
            $errors[] = "Owner has {$incompleteRemittances} incomplete remittance(s) that must be resolved first";
        }

        return $errors;
    }

    /**
     * Boot method to add model-level validation constraints
     */
    protected static function boot()
    {
        parent::boot();

        // Add validation before creating an owner
        static::creating(function ($owner) {
            self::validateOwnerData($owner);
        });

        // Add validation before updating an owner
        static::updating(function ($owner) {
            self::validateOwnerData($owner);
        });

        // Add validation after saving to ensure data consistency
        static::saved(function ($owner) {
            self::validateOwnerConsistency($owner);
        });
    }

    /**
     * Validate owner data to prevent invalid record creation
     */
    protected static function validateOwnerData(Owner $owner): void
    {
        $errors = [];

        // Validate required fields
        if (!$owner->name || trim($owner->name) === '') {
            $errors[] = 'Owner name is required and cannot be empty';
        }

        // Validate name length
        if ($owner->name && strlen($owner->name) > 255) {
            $errors[] = 'Owner name cannot exceed 255 characters';
        }

        // Validate phone format if provided
        if ($owner->phone && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,20}$/', $owner->phone)) {
            $errors[] = 'Owner phone number format is invalid';
        }

        // Validate user_id if provided
        if ($owner->user_id) {
            $user = User::find($owner->user_id);
            if (!$user) {
                $errors[] = 'Associated user does not exist';
            }
        }

        // Throw validation exception if there are errors
        if (!empty($errors)) {
            \Log::error('Owner model validation failed', [
                'owner_data' => $owner->toArray(),
                'validation_errors' => $errors
            ]);

            throw new \InvalidArgumentException('Owner validation failed: ' . implode(', ', $errors));
        }
    }

    /**
     * Validate owner data consistency after saving
     */
    protected static function validateOwnerConsistency(Owner $owner): void
    {
        $warnings = [];

        // Check if owner has properties but no user association
        if ($owner->properties()->count() > 0 && !$owner->hasValidUserAssociation()) {
            $warnings[] = 'Owner has properties but no valid user association';
        }

        // Check if owner has remittances but invalid property setup
        if ($owner->remittance()->count() > 0 && !$owner->hasValidPropertyForRemittance()) {
            $warnings[] = 'Owner has remittances but invalid property setup';
        }

        // Log warnings for monitoring
        if (!empty($warnings)) {
            \Log::warning('Owner data consistency issues detected', [
                'owner_id' => $owner->id,
                'owner_name' => $owner->name,
                'consistency_warnings' => $warnings
            ]);
        }
    }

    /**
     * Validate that owner can be safely deleted
     */
    public function canBeDeleted(): array
    {
        $issues = [];

        // Check for active remittances
        $activeRemittances = $this->remittance()
            ->whereIn('status', ['Pending', 'Partial'])
            ->count();

        if ($activeRemittances > 0) {
            $issues[] = "Owner has {$activeRemittances} active remittance(s) that must be completed first";
        }

        // Check for properties with active contracts
        foreach ($this->properties as $property) {
            $activeContracts = $property->flats()
                ->whereHas('contracts', function ($query) {
                    $query->where('status', 'active');
                })
                ->count();

            if ($activeContracts > 0) {
                $issues[] = "Property '{$property->name}' has {$activeContracts} active contract(s)";
            }
        }

        return $issues;
    }

    /**
     * Safely delete owner with validation
     */
    public function safeDelete(): bool
    {
        $issues = $this->canBeDeleted();

        if (!empty($issues)) {
            \Log::warning('Owner deletion prevented due to data integrity issues', [
                'owner_id' => $this->id,
                'owner_name' => $this->name,
                'deletion_issues' => $issues
            ]);

            throw new \InvalidArgumentException('Cannot delete owner: ' . implode(', ', $issues));
        }

        return $this->delete();
    }
}

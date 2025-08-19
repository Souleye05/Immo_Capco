<?php

namespace App\Models;

use App\Enums\RemittanceType;
use App\Enums\RemittanceStatus;
use App\Models\Agency;
use App\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;

class Remittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'property_id',
        'numero',
        'status',
        'mode_remit',
        'remittance_type',
        'current_month',
        'current_year',
        'amount',
        'remittance_date',
        'amount_to_transfer',
        'remaining',
    ];

    protected $casts = [
        'remittance_type' => RemittanceType::class,
        'status' => RemittanceStatus::class,
        'amount' => 'decimal:2',
        'amount_to_transfer' => 'decimal:2',
        'remaining' => 'decimal:2',
        'remittance_date' => 'date',
        'current_month' => 'integer',
        'current_year' => 'integer',
    ];

    /**
     * Boot method to add model-level validation constraints
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($remittance) {
            $remittance->validateRemittanceData();
        });

        static::updating(function ($remittance) {
            $remittance->validateRemittanceData();
        });
    }

    // ===========================================
    // VALIDATION METHODS
    // ===========================================

    /**
     * Validate remittance data to prevent invalid record creation
     */
    public function validateRemittanceData(): void
    {
        $validator = new RemittanceValidator($this);
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            
            Log::error('Remittance model validation failed', [
                'remittance_data' => $this->toArray(),
                'validation_errors' => $errors
            ]);

            throw ValidationException::withMessages([
                'remittance' => $errors
            ]);
        }
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if remittance is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->status === RemittanceStatus::PAID && $this->remaining <= 0;
    }

    /**
     * Check if remittance is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === RemittanceStatus::PARTIAL && $this->remaining > 0;
    }

    /**
     * Check if remittance is pending
     */
    public function isPending(): bool
    {
        return $this->status === RemittanceStatus::PENDING;
    }

    /**
     * Mark remittance as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => RemittanceStatus::PAID,
            'remaining' => 0
        ]);
    }

    /**
     * Update partial payment
     */
    public function updatePartialPayment(float $paidAmount): void
    {
        $newRemaining = max(0, $this->amount - $paidAmount);
        
        $this->update([
            'status' => $newRemaining > 0 ? RemittanceStatus::PARTIAL : RemittanceStatus::PAID,
            'remaining' => $newRemaining,
            'amount_to_transfer' => $paidAmount
        ]);
    }

    /**
     * Get formatted remittance number
     */
    public function getFormattedNumero(): string
    {
        return $this->remittance_type->getPrefix() . '-' . $this->numero;
    }

    /**
     * Get remittance period (for rent type)
     */
    public function getPeriod(): ?string
    {
        if ($this->remittance_type !== RemittanceType::LOYER) {
            return null;
        }

        return sprintf('%02d/%d', $this->current_month, $this->current_year);
    }

    /**
     * Get commission amount based on property settings
     */
    public function getCommissionAmount(): float
    {
        if (!$this->property) {
            return 0;
        }

        return $this->property->calculateCommission($this->amount);
    }

    /**
     * Get net amount after commission
     */
    public function getNetAmount(): float
    {
        return $this->amount - $this->getCommissionAmount();
    }

    // ===========================================
    // QUERY SCOPES
    // ===========================================

    /**
     * Scope for filtering by remittance type
     */
    public function scopeOfType($query, RemittanceType $type)
    {
        return $query->where('remittance_type', $type);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeWithStatus($query, RemittanceStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for rent remittances
     */
    public function scopeRent($query)
    {
        return $query->where('remittance_type', RemittanceType::LOYER);
    }

    /**
     * Scope for deposit remittances
     */
    public function scopeDeposit($query)
    {
        return $query->where('remittance_type', RemittanceType::CAUTION);
    }

    /**
     * Scope for pending remittances
     */
    public function scopePending($query)
    {
        return $query->where('status', RemittanceStatus::PENDING);
    }

    /**
     * Scope for paid remittances
     */
    public function scopePaid($query)
    {
        return $query->where('status', RemittanceStatus::PAID);
    }

    /**
     * Scope for partial remittances
     */
    public function scopePartial($query)
    {
        return $query->where('status', RemittanceStatus::PARTIAL);
    }

    /**
     * Scope for remittances by period
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('current_month', $month)
                    ->where('current_year', $year);
    }

    /**
     * Scope for remittances by agency (via property)
     */
    public function scopeForAgency($query, int $agencyId)
    {
        return $query->whereHas('property', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        });
    }

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function remittancePartials(): HasMany
    {
        return $this->hasMany(RemittancePartial::class);
    }

    /**
     * Get agency through property relationship
     */
    public function agency()
    {
        return $this->hasOneThrough(Agency::class, Property::class, 'id', 'id', 'property_id', 'agency_id');
    }

    // ===========================================
    // TENANT SCOPING
    // ===========================================

    /**
     * Boot method to add tenant scoping
     */
    protected static function booted()
    {
        parent::booted();

        // Add global scope for tenant filtering
        static::addGlobalScope('tenant', function ($builder) {
            if (Filament::getTenant()) {
                $builder->whereHas('property', function ($query) {
                    $query->where('agency_id', Filament::getTenant()->getKey());
                });
            }
        });
    }

    /**
     * Get agency through property relationship (for tenant scoping)
     */
    public function agencys()
    {
        return $this->hasOneThrough(Agency::class, Property::class, 'id', 'id', 'property_id', 'agency_id');
    }

    // ===========================================
    // ACCESSORS & MUTATORS
    // ===========================================

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Get type label for display
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->remittance_type->label();
    }

    /**
     * Get payment progress percentage
     */
    public function getPaymentProgressAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        $paid = $this->amount - $this->remaining;
        return round(($paid / $this->amount) * 100, 2);
    }
}

// ===========================================
// SEPARATE VALIDATOR CLASS
// ===========================================

class RemittanceValidator
{
    private Remittance $remittance;
    private array $errors = [];

    public function __construct(Remittance $remittance)
    {
        $this->remittance = $remittance;
    }

    public function isValid(): bool
    {
        $this->errors = [];
        
        $this->validateOwner();
        $this->validateProperty();
        $this->validateOwnerPropertyRelationship();
        $this->validateRemittanceType();
        $this->validateBasicFields();
        $this->validateAmounts();
        $this->validateDuplicates();

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function validateOwner(): void
    {
        if (!$this->remittance->owner_id) {
            $this->errors[] = 'Owner ID is required for remittance creation';
            return;
        }

        $owner = Owner::find($this->remittance->owner_id);
        if (!$owner) {
            $this->errors[] = 'Owner with ID ' . $this->remittance->owner_id . ' does not exist';
            return;
        }

        if (!$owner->hasValidPropertyForRemittance()) {
            $validationErrors = $owner->getRemittanceValidationErrors();
            $this->errors[] = 'Owner cannot create remittance: ' . implode(', ', $validationErrors);
        }
    }

    private function validateProperty(): void
    {
        if (!$this->remittance->property_id) {
            $this->errors[] = 'Property ID is required for remittance creation';
            return;
        }

        $property = Property::find($this->remittance->property_id);
        if (!$property) {
            $this->errors[] = 'Property with ID ' . $this->remittance->property_id . ' does not exist';
            return;
        }

        if (!$property->agency_id) {
            $this->errors[] = 'Property must be associated with an agency';
        }

        if ($property->flats()->count() === 0) {
            $this->errors[] = 'Property must have associated flats for payment calculations';
        }

        if (!$property->commission_value || !$property->commission_unit) {
            $this->errors[] = 'Property must have commission settings configured';
        }
    }

    private function validateOwnerPropertyRelationship(): void
    {
        if (!$this->remittance->owner_id || !$this->remittance->property_id) {
            return;
        }

        $owner = Owner::find($this->remittance->owner_id);
        if (!$owner) {
            return;
        }

        // Vérifier que la propriété appartient bien à ce propriétaire
        $ownerProperty = $owner->properties()->where('id', $this->remittance->property_id)->first();
        if (!$ownerProperty) {
            $this->errors[] = 'Property does not belong to the specified owner';
        }
    }

    private function validateRemittanceType(): void
    {
        if (!$this->remittance->remittance_type) {
            $this->errors[] = 'Remittance type is required';
            return;
        }

        if ($this->remittance->remittance_type === RemittanceType::LOYER) {
            $this->validateRentRemittance();
        }
    }

    private function validateRentRemittance(): void
    {
        if (!$this->remittance->current_month || $this->remittance->current_month < 1 || $this->remittance->current_month > 12) {
            $this->errors[] = 'Valid month (1-12) is required for rent remittance';
        }

        if (!$this->remittance->current_year || $this->remittance->current_year < 2020 || $this->remittance->current_year > 2030) {
            $this->errors[] = 'Valid year (2020-2030) is required for rent remittance';
        }
    }

    private function validateBasicFields(): void
    {
        if (!$this->remittance->numero) {
            $this->errors[] = 'Remittance number is required';
        }

        if (!$this->remittance->status) {
            $this->errors[] = 'Remittance status is required';
        }

        if (!$this->remittance->remittance_date) {
            $this->errors[] = 'Remittance date is required';
        }
    }

    private function validateAmounts(): void
    {
        if ($this->remittance->amount < 0) {
            $this->errors[] = 'Remittance amount cannot be negative';
        }

        if ($this->remittance->amount_to_transfer < 0) {
            $this->errors[] = 'Amount to transfer cannot be negative';
        }

        if ($this->remittance->remaining < 0) {
            $this->errors[] = 'Remaining amount cannot be negative';
        }
    }

    private function validateDuplicates(): void
    {
        if ($this->remittance->remittance_type !== RemittanceType::LOYER) {
            return;
        }

        if (!$this->remittance->property_id || !$this->remittance->current_month || !$this->remittance->current_year) {
            return;
        }

        $existingQuery = Remittance::where('property_id', $this->remittance->property_id)
            ->where('remittance_type', RemittanceType::LOYER)
            ->where('current_month', $this->remittance->current_month)
            ->where('current_year', $this->remittance->current_year);

        if ($this->remittance->exists) {
            $existingQuery->where('id', '!=', $this->remittance->id);
        }

        if ($existingQuery->exists()) {
            $this->errors[] = 'Rent remittance already exists for this property and period';
        }
    }
}
<?php

namespace App\Models;

use App\Enums\RemittanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

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
    ];

    /**
     * Boot method to add model-level validation constraints
     */
    protected static function boot()
    {
        parent::boot();

        // Add validation before creating a remittance
        static::creating(function ($remittance) {
            self::validateRemittanceData($remittance);
        });

        // Add validation before updating a remittance
        static::updating(function ($remittance) {
            self::validateRemittanceData($remittance);
        });
    }

    /**
     * Validate remittance data to prevent invalid record creation
     */
    protected static function validateRemittanceData(Remittance $remittance): void
    {
        $errors = [];

        // Validate owner_id is not null and owner exists
        if (!$remittance->owner_id) {
            $errors[] = 'Owner ID is required for remittance creation';
        } else {
            $owner = Owner::find($remittance->owner_id);
            if (!$owner) {
                $errors[] = 'Owner with ID ' . $remittance->owner_id . ' does not exist';
            } else {
                // Validate owner can create remittance
                if (!$owner->hasValidPropertyForRemittance()) {
                    $validationErrors = $owner->getRemittanceValidationErrors();
                    $errors[] = 'Owner cannot create remittance: ' . implode(', ', $validationErrors);
                }
            }
        }

        // Validate property_id is not null and property exists
        if (!$remittance->property_id) {
            $errors[] = 'Property ID is required for remittance creation';
        } else {
            $property = Property::find($remittance->property_id);
            if (!$property) {
                $errors[] = 'Property with ID ' . $remittance->property_id . ' does not exist';
            } else {
                // Validate property has required data for remittance
                if (!$property->agency_id) {
                    $errors[] = 'Property must be associated with an agency';
                }

                if ($property->flats()->count() === 0) {
                    $errors[] = 'Property must have associated flats for payment calculations';
                }

                if (!$property->commission_value || !$property->commission_unit) {
                    $errors[] = 'Property must have commission settings configured';
                }
            }
        }

        // Validate owner-property relationship
        if ($remittance->owner_id && $remittance->property_id) {
            $owner = Owner::find($remittance->owner_id);
            if ($owner) {
                $ownerProperty = $owner->property();
                if (!$ownerProperty || $ownerProperty->id !== $remittance->property_id) {
                    $errors[] = 'Property does not belong to the specified owner';
                }
            }
        }

        // Validate remittance type
        if (!$remittance->remittance_type) {
            $errors[] = 'Remittance type is required';
        } else {
            $validTypes = ['loyer', 'caution'];
            $typeValue = $remittance->remittance_type instanceof RemittanceType
                ? $remittance->remittance_type->value
                : $remittance->remittance_type;

            if (!in_array($typeValue, $validTypes)) {
                $errors[] = 'Invalid remittance type: ' . $typeValue;
            }

            // Additional validation for rent type
            if ($typeValue === 'loyer') {
                if (!$remittance->current_month || $remittance->current_month < 1 || $remittance->current_month > 12) {
                    $errors[] = 'Valid month (1-12) is required for rent remittance';
                }

                if (!$remittance->current_year || $remittance->current_year < 2020 || $remittance->current_year > 2030) {
                    $errors[] = 'Valid year (2020-2030) is required for rent remittance';
                }

                // Check for duplicate rent remittance
                if ($remittance->property_id && $remittance->current_month && $remittance->current_year) {
                    $existingQuery = self::where('property_id', $remittance->property_id)
                        ->where('remittance_type', 'loyer')
                        ->where('current_month', $remittance->current_month)
                        ->where('current_year', $remittance->current_year);

                    // Exclude current record if updating
                    if ($remittance->exists) {
                        $existingQuery->where('id', '!=', $remittance->id);
                    }

                    if ($existingQuery->exists()) {
                        $errors[] = 'Rent remittance already exists for this property and period';
                    }
                }
            }
        }

        // Validate numero is not empty
        if (!$remittance->numero) {
            $errors[] = 'Remittance number is required';
        }

        // Validate status
        if (!$remittance->status) {
            $errors[] = 'Remittance status is required';
        } else {
            $validStatuses = ['Pending', 'Partial', 'Paid'];
            if (!in_array($remittance->status, $validStatuses)) {
                $errors[] = 'Invalid remittance status: ' . $remittance->status;
            }
        }

        // Validate amounts are not negative
        if ($remittance->amount < 0) {
            $errors[] = 'Remittance amount cannot be negative';
        }

        if ($remittance->amount_to_transfer < 0) {
            $errors[] = 'Amount to transfer cannot be negative';
        }

        if ($remittance->remaining < 0) {
            $errors[] = 'Remaining amount cannot be negative';
        }

        // Validate remittance_date
        if (!$remittance->remittance_date) {
            $errors[] = 'Remittance date is required';
        }

        // Throw validation exception if there are errors
        if (!empty($errors)) {
            \Log::error('Remittance model validation failed', [
                'remittance_data' => $remittance->toArray(),
                'validation_errors' => $errors
            ]);

            throw ValidationException::withMessages([
                'remittance' => $errors
            ]);
        }
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function remittancePartials()
    {
        return $this->hasMany(RemittancePartial::class);
    }

    // Relation pour le tenant scoping via property
    public function agencys(): BelongsTo
    {
        // Retourne l'agence via la relation property
        return $this->belongsTo(Agency::class, 'property_id', 'id')
            ->join('properties', 'agencies.id', '=', 'properties.agency_id')
            ->where('properties.id', $this->property_id)
            ->select('agencies.*');
    }
}

<?php

namespace App\Models;

// use FlatType;

use App\Enums\ContractStatus;
use App\Enums\FlatType;
use App\Models\Agency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Flat extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'property_commission_value',
        'property_commission_unit',
        'reference',
        'designation',
        'level',
        'type',
        'loyer',
        'caution',
    ];

    protected $casts = [
        'type' => FlatType::class, // Assuming FlatType is a string enum
        'level' => 'string',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function currentTenant()
    {
        return $this->hasOneThrough(
            Tenant::class,
            Contract::class,
            'flat_id',     // Foreign key sur contracts table
            'id',          // Foreign key sur tenants table  
            'id',          // Local key sur flats table
            'tenant_id'    // Local key sur contracts table
        )->where('contracts.status', ContractStatus::ACTIVE);
    }
    public function property()
    {
        return $this->belongsTo(Property::class);
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

    // public function tenant()
    // {
    //     return $this->belongsTo(Tenant::class, 'tenant_id');
    // }
    public function getCurrentTenantAttribute()
    {
        return $this->activeContract?->tenant;
    }



    public function activeContract()
    {
        return $this->hasOne(Contract::class)
            ->where('status', ContractStatus::ACTIVE)
            ->latest('start_date');
    }


    public function getIsOccupiedAttribute(): bool
    {
        return $this->activeContract()->exists();
    }

    public function getStatusAttribute(): string
    {
        return $this->is_occupied ? 'Occupé' : 'Libre';
    }
    public function getOccupancyDateAttribute()
    {
        $activeContract = $this->contracts()
            ->where('status', ContractStatus::ACTIVE)
            ->first();

        return $activeContract?->start_date;
    }

    // expenses
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    public static function options(): array
    {
        return FlatType::options();
    }

    public function scopeAvailable($query)
    {
        return $query->whereDoesntHave('contracts', function ($q) {
            $q->where('status', ContractStatus::ACTIVE);
        })->whereNull('tenant_id');
    }

    public function scopeOccupied($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('contracts', function ($subQ) {
                $subQ->where('status', ContractStatus::ACTIVE);
            })->orWhereNotNull('tenant_id');
        });
    }
    public function getFullDescriptionAttribute(): string
    {
        $description = $this->designation ?: $this->type->label();

        if ($this->reference) {
            $description .= ' (' . $this->reference . ')';
        }

        if ($this->level) {
            $description .= ' - Niveau ' . $this->level;
        }

        return $description;
    }
}

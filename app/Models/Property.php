<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\PropertyType;
use App\Enums\CommissionUnit;
use App\Traits\TenantScoped;

class Property extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'agency_id',
        'owner_id',
        'name',
        'address',
        'type',
        'number_flat',
        'commission_value',
        'commission_unit',
    ];
    protected $casts = [
        'type' => PropertyType::class,
        'commission_unit' => CommissionUnit::class
    ];

    public function flats()
    {
        return $this->hasMany(Flat::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // Relation pour le tenant scoping (pluriel)
    public function agencys(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function remit()
    {
        return $this->hasMany(Remittance::class, 'property_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContracts()
    {
        return $this->hasMany(Contract::class)->where('status', \App\Enums\ContractStatus::ACTIVE);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Contract::class);
    }

    public function getFullNameAttribute()
    {
        return $this->type->value . ' - ' . $this->name;
    }
}

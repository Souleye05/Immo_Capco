<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'agency_id',
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'flat_id',
    ];

    // public function flat()
    // {
    //     return $this->hasOne(Flat::class, 'tenant_id');
    // }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // Relation pour le tenant scoping (pluriel)
    public function agencys(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasMany(Payment::class, 'tenant_id');
    }

    public function unsold()
    {
        return $this->hasMany(Unsold::class);
    }

    public function scopeByFlatId($query, $flatId)
    {
        return $query->where('flat_id', $flatId);
    }
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', 'like', "%{$phone}%");
    }
    public function scopeByAddress($query, $address)
    {
        return $query->where('address', 'like', "%{$address}%");
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('status', ContractStatus::ACTIVE);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }


    public function flatThroughContract()
    {
        return $this->hasOneThrough(
            Flat::class,
            Contract::class,
            'tenant_id',   // Foreign key on contracts table
            'id',          // Foreign key on flats table
            'id',          // Local key on tenants table
            'flat_id'      // Local key on contracts table
        )->where('contracts.status', ContractStatus::ACTIVE);
    }

    // Scope for tenant panel functionality
    public function scopeForTenantPanel($query)
    {
        return $query->with(['contracts', 'payment', 'agency']);
    }

    // Get all payments through contracts
    public function allPayments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Contract::class,
            'tenant_id',   // Foreign key on contracts table
            'contract_id', // Foreign key on payments table
            'id',          // Local key on tenants table
            'id'           // Local key on contracts table
        );
    }
}

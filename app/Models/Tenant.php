<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'flat_id',
    ];

    // public function flat()
    // {
    //     return $this->hasOne(Flat::class, 'tenant_id');
    // }

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
}

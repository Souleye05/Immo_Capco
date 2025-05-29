<?php

namespace App\Models;           

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'flat_id'
    ];

    public function flat()
    {
        return $this->hasOne(Flat::class, 'tenant_id');
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
}

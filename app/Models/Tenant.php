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
}

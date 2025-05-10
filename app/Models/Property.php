<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'type',
        'number_flat',
        'commission_value',
        'commission_unit',
    ];

    public function flats()
    {
        return $this->hasMany(Flat::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function owner()
    {
        return $this->hasOne(Owner::class);
    }

    public function remit()
    {
        return $this->hasMany(Remittance::class, 'property_id');
    }

    public function getFullNameAttribute()
    {
        return $this->type . ' - ' . $this->name;
    }
}

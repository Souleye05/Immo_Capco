<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flat extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'tenant_id',
        'property_commission_value',
        'property_commission_unit',
        'reference',
        'type',
        'loyer',
        'caution',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    // expenses
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}

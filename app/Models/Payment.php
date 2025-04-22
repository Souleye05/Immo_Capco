<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'flat_id',
        'tenant_id',
        'numero',
        'current_month',
        'amount',
        'status',
        'date_payment',
        'payment_method',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    /* public function unsold()
    {
        return $this->hasMany(Unsold::class);
    } */

    public function versement()
    {
        return $this->hasMany(Versement::class);
    }
}

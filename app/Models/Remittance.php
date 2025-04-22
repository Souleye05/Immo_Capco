<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'property_id',
        'mode_remit',
        'amount',
        'remittance_date',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'property_id');
    }

    public function remit()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}

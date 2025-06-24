<?php

namespace App\Models;

use App\Enums\RemittanceType;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'property_id',
        'numero',
        'status',
        'mode_remit',
        'remittance_type',
        'current_month',
        'current_year',
        'amount',
        'remittance_date',
        'amount_to_transfer',
        'remaining',
    ];

    protected $casts = [
        'remittance_type' => RemittanceType::class,
    ];

   public function owner()
{
    return $this->belongsTo(Owner::class, 'owner_id'); 
}

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    
    public function remittancePartials()
    {
        return $this->hasMany(RemittancePartial::class);
    }

}

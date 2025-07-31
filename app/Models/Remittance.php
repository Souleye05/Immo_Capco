<?php

namespace App\Models;

use App\Enums\RemittanceType;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // Relation pour le tenant scoping via property
    public function agencys(): BelongsTo
    {
        // Retourne l'agence via la relation property
        return $this->belongsTo(Agency::class, 'property_id', 'id')
            ->join('properties', 'agencies.id', '=', 'properties.agency_id')
            ->where('properties.id', $this->property_id)
            ->select('agencies.*');
    }
}

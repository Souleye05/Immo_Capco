<?php

namespace App\Models;

use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'property_id',
        'status',
        'mode_remit',
        'current_month',
        'current_year',
        'amount',
        'remittance_date',
        'amount_to_transfer',
        'remaining',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'property_id');
    }

    public function remit()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    
    public function remittancePartials()
    {
        return $this->hasMany(RemittancePartial::class);
    }
    protected static function booted()
    {
        static::saving(function ($remittance) {
            $paymentService = app(PaymentService::class);
            $stats = $paymentService->getPropertyFinancialStats($remittance->property_id, now()->month, now()->year);

            // Montant total à transférer
            $remittance->amount_to_transfer = $stats['amount_to_transfer'] ?? 0;

            // Montant déjà payé (inclut le montant actuel en cours de sauvegarde)
            $alreadyPaid = $remittance->remittancePartials()->sum('amount');


            // Reste à verser
            $remittance->amount = $alreadyPaid;
            $remittance->remaining = $remittance->amount_to_transfer - $alreadyPaid;

        });
    }

}

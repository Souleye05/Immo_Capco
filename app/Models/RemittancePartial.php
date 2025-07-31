<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemittancePartial extends Model
{
    //
    protected $fillable = [
        'remittance_id',
        'reference',
        'amount',
        'mode_remit',
        'receipt_path',
        'current_month',
        'remittance_date',
    ];

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    // Relation pour le tenant scoping via remittance -> property
    public function agencys(): BelongsTo
    {
        // Retourne l'agence via la relation remittance -> property
        return $this->belongsTo(Agency::class, 'remittance_id', 'id')
            ->join('remittances', 'agencies.id', '=', 'properties.agency_id')
            ->join('properties', 'remittances.property_id', '=', 'properties.id')
            ->where('remittances.id', $this->remittance_id)
            ->select('agencies.*');
    }

    protected static function booted()
    {
        static::created(function ($partial) {
            $service = app(InvoiceService::class);

            $fileName = 'quittance_remittance_' . $partial->id . '.pdf';

            $path = $service->generateAndStorePdf(
                'invoices.receipt',
                ['partial' => $partial],
                $fileName
            );

            $partial->update(['receipt_path' => $path]);
        });
    }
}

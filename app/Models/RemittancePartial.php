<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\InvoiceService;

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

protected static function booted()
{
    static::created(function ($partial) {
        $service = app(InvoiceService::class);

        $fileName = 'quittance_remittance_' . $partial->id . '.pdf';

        // Récupérer tous les versements partiels liés au même remittance
        $partials = self::where('remittance_id', $partial->remittance_id)->get();

        $path = $service->generateAndStorePdf(
            'invoices.receipt',
            [
                'partial' => $partial, // le dernier ajouté
                'partials' => $partials, // tous les versements liés
                'remittance' => $partial->remittance, // pour les données globales si besoin
            ],
            $fileName
        );

        $partial->update(['receipt_path' => $path]);
    });
}


}

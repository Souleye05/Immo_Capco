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

        $path = $service->generateAndStorePdf(
            'invoices.receipt',
            ['partial' => $partial],
            $fileName
        );

        $partial->update(['receipt_path' => $path]);
    });
}

}

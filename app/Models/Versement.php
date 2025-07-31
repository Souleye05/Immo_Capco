<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Versement extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'reference',
        'amount',
        'payment_method',
        'current_month',
        'versement_date',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    // Relation pour le tenant scoping via payment
    public function agencys(): BelongsTo
    {
        // Retourne l'agence via la relation payment
        return $this->belongsTo(Agency::class, 'payment_id', 'id')
            ->join('payments', 'agencies.id', '=', 'payments.agency_id')
            ->where('payments.id', $this->payment_id)
            ->select('agencies.*');
    }
}

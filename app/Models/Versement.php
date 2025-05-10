<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unsold extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payment_id',
        'is_archived',
        'month',
        'tenant_id',
        'amount',
        'motif',
        'reference',
        'status',
        'date',
        'paid_at',
    ];

    protected $dates = [
        'due_date',
        'paid_at',
        'deleted_at', // Important pour SoftDeletes
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment()
{
    return $this->belongsTo(Payment::class);
}

    public function versementimp()
    {
        return $this->hasMany(versementimp::class);
    }

    // Accessor pour le montant à verser
    public function getAmountToPayAttribute()
    {
        $payments = Payment::where('tenant_id', $this->tenant_id)->get();
        return $payments->sum('amount'); // Somme des montants à verser
    }

    // Accessor pour le montant versé
    public function getAmountPaidAttribute()
    {
        $payments = Payment::where('tenant_id', $this->tenant_id)->get();
        return $payments->sum('amount_paid'); // Somme des montants versés
    }

    // Accessor pour le montant restant
    public function getAmountRemainingAttribute()
    {
        return max(0, $this->amount_to_pay - $this->amount_paid); // Montant restant
    }
    
    public static function restoreUnsolds($unsoldIds)
    {
        return self::withTrashed()
            ->whereIn('id', $unsoldIds)
            ->restore();
    }
    }


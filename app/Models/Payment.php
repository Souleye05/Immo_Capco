<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'flat_id',
        'tenant_id',
        'numero',
        'current_month',
        'amount',
        'status',
        'date_payment',
        // 'payment_method',
    ];
// Définir une méthode pour vérifier si la facture est complète
public function isComplete(): bool
{
    return $this->amount_paid >= $this->amount;
}


    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class);
    }

    public function unsolds()
{
    return $this->hasMany(Unsold::class, 'tenant_id', 'tenant_id');
}

    public function versement()
    {
        return $this->hasMany(Versement::class);
    }
    
    // Accessor pour le montant versé
    public function getAmountPaidAttribute(): float
    {
        return $this->versement()->sum('amount'); // Somme des versements associés
    }
    // Accessor pour le montant restant
    public function getAmountRemainingAttribute(): float
    {
        return max(0, $this->amount - $this->amount_paid); // Montant dû - Montant versé
    }
}

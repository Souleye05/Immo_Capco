<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'flat_id',
        'prestataire_id',
        'categorie_depense_id',
        'titre',
        'type',
        'libelle',
        'amount',
        'payment_date',
        'payment_method',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class);
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieDepense::class, 'categorie_depense_id');
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class, 'flat_id');
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    return $this->belongsTo(Property::class);
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
}



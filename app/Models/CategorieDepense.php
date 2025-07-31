<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorieDepense extends Model
{
    //
    use HasFactory;
    protected $fillable = ['categorie', 'description'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // Relation factice pour le tenant scoping Filament
    // Les catégories de dépenses sont globales, pas liées à une agence spécifique
    public function agencys(): BelongsTo
    {
        // Retourne une relation vide - les catégories sont globales
        return $this->belongsTo(Agency::class, 'id', 'id')->whereRaw('1 = 0');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestataire extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'nom',
        'profession',
        'phone',
        'adresse',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getFullNameAttribute()
    {
        // return $this->nom . ' ' . $this->profession;
        return "{$this->nom} - {$this->profession}";
    }

    // Relation factice pour le tenant scoping Filament
    // Les prestataires peuvent travailler pour différentes agences
    public function agencys(): BelongsTo
    {
        // Retourne une relation vide - les prestataires sont gérés globalement
        return $this->belongsTo(Agency::class, 'id', 'id')->whereRaw('1 = 0');
    }
}

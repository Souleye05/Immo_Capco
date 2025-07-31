<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Owner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'property_id',
        'user_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remittance()
    {
        return $this->hasMany(Remittance::class);
    }

    public function remittancePartials()
    {
        return $this->hasMany(RemittancePartial::class);
    }

    // Relation factice pour le tenant scoping Filament
    // Les owners peuvent avoir des propriétés dans différentes agences
    public function agencys(): BelongsTo
    {
        // Retourne une relation vide - les owners sont gérés globalement
        return $this->belongsTo(Agency::class, 'id', 'id')->whereRaw('1 = 0');
    }
}

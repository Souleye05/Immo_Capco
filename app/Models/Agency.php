<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'name',
        'slug',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function remittances()
    {
        return $this->hasManyThrough(Remittance::class, Property::class);
    }

    public function prestataires(): HasMany
    {
        // Relation factice - les prestataires sont globaux, pas liés à une agence spécifique
        return $this->hasMany(Prestataire::class, 'id', 'id')->whereRaw('1 = 0');
    }

    public function prospects(): HasMany
    {
        // Relation factice - les prospects peuvent être gérés par différentes agences
        return $this->hasMany(Prospect::class, 'id', 'id')->whereRaw('1 = 0');
    }

    public function categorieDepenses(): HasMany
    {
        // Relation factice - les catégories de dépenses sont globales
        return $this->hasMany(CategorieDepense::class, 'id', 'id')->whereRaw('1 = 0');
    }

    public function owners(): HasMany
    {
        // Relation factice - les owners peuvent avoir des propriétés dans différentes agences
        return $this->hasMany(Owner::class, 'id', 'id')->whereRaw('1 = 0');
    }

    public function unsolds(): HasMany
    {
        return $this->hasManyThrough(Unsold::class, Tenant::class);
    }

    /**
     * Custom roles created for this agency
     */
    public function agencyRoles(): HasMany
    {
        return $this->hasMany(AgencyRole::class);
    }

    /**
     * Get agency owners (users with agency-owner role)
     */
    public function agencyOwners(): BelongsToMany
    {
        return $this->members()->whereHas('roles', function ($query) {
            $query->where('name', 'agency-owner');
        });
    }
}

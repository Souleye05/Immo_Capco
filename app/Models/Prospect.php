<?php

namespace App\Models;

use App\Enums\ProspectObjetEnum;
use App\Enums\ProspectTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'objet',
        'type',
        'budget',
        'secteur_localisation'
    ];

    protected $casts = [
        'objet' => 'array',
        'type' => 'array',

    ];



    public function scopeByObjet(Builder $query, string|ProspectObjetEnum $objet): Builder
    {
        $objetValue = $objet instanceof ProspectObjetEnum ? $objet->value : $objet;

        return $query->whereJsonContains('objet', $objetValue);
    }

    public function scopeByType(Builder $query, string|ProspectTypeEnum $type): Builder
    {
        $typeValue = $type instanceof ProspectTypeEnum ? $type->value : $type;

        return $query->whereJsonContains('type', $typeValue);
    }

    // public function scopeByBudgetRange(Builder $query, ?string $minBudget = null, ?string $maxBudget = null): Builder
    // {
    //     if (!$minBudget && !$maxBudget) return $query;

    //     return $query->where(function($q) use ($minBudget, $maxBudget) {
    //         if ($minBudget) {
    //             $q->where('budget', 'like', "%{$minBudget}%");
    //         }
    //         if ($maxBudget) {
    //             $q->where('budget', 'like', "%{$maxBudget}%");
    //         }
    //     });
    // }



    // Mutator/Accessor pour le budget avec formatage
    protected function budget(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value,
            set: fn(?string $value) => $value ? trim($value) : null,
        );
    }

    // Mutator/Accessor pour le secteur avec formatage
    protected function secteurLocalisation(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value,
            set: fn(?string $value) => $value ? ucwords(strtolower(trim($value))) : null,
        );
    }

    // Scope pour filtrer par objet
    public function scopeWhereObjet(Builder $query, string|array $objet): Builder
    {
        $objets = is_array($objet) ? $objet : [$objet];

        return $query->where(function ($q) use ($objets) {
            foreach ($objets as $obj) {
                $q->orWhereJsonContains('objet', $obj);
            }
        });
    }

    // Scope pour filtrer par type
    public function scopeWhereType(Builder $query, string|array $type): Builder
    {
        $types = is_array($type) ? $type : [$type];

        return $query->where(function ($q) use ($types) {
            foreach ($types as $type) {
                $q->orWhereJsonContains('type', $type);
            }
        });
    }

    // Scope pour filtrer par secteur
    public function scopeBySecteur(Builder $query, string $secteur): Builder
    {
        return $query->where('secteur_localisation', 'LIKE', "%{$secteur}%");
    }

    // Scope pour les prospects avec budget défini
    public function scopeWithBudget(Builder $query): Builder
    {
        return $query->whereNotNull('budget');
    }

    // Accesseur pour afficher les objets sous forme lisible
    public function getObjetLabelsAttribute(): string
    {
        if (!$this->objet) {
            return '-';
        }

        return collect($this->objet)
            ->map(fn($obj) => ProspectObjetEnum::tryFrom($obj)?->label() ?? $obj)
            ->join(', ');
    }

    // Accesseur pour afficher les types sous forme lisible
    public function getTypeLabelsAttribute(): string
    {
        if (!$this->type) {
            return '-';
        }

        return collect($this->type)
            ->map(fn($type) => ProspectTypeEnum::tryFrom($type)?->label() ?? $type)
            ->join(', ');
    }

    // Méthode pour vérifier si le prospect correspond à des critères
    public function matchesCriteria(array $objets = [], array $types = [], string $secteur = null): bool
    {
        $objetMatch = empty($objets) || !empty(array_intersect($this->objet ?? [], $objets));
        $typeMatch = empty($types) || !empty(array_intersect($this->type ?? [], $types));
        $secteurMatch = !$secteur || str_contains(strtolower($this->secteur_localisation ?? ''), strtolower($secteur));

        return $objetMatch && $typeMatch && $secteurMatch;
    }

    // Relation factice pour le tenant scoping Filament
    // Les prospects peuvent être gérés par différentes agences
    public function agencys(): BelongsTo
    {
        // Retourne une relation vide - les prospects sont gérés globalement
        return $this->belongsTo(Agency::class, 'id', 'id')->whereRaw('1 = 0');
    }
}

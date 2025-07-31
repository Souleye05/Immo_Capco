<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersementImp extends Model
{
    use HasFactory;

    protected $fillable = [
        'unsold_id',
        'reference',
        'amount',
        'current_month',
        'versement_date',
    ];

    public function unsold()
    {
        return $this->belongsTo(Unsold::class);
    }

    // Relation pour le tenant scoping via unsold -> tenant
    public function agencys(): BelongsTo
    {
        // Retourne l'agence via la relation unsold -> tenant
        return $this->belongsTo(Agency::class, 'unsold_id', 'id')
            ->join('unsolds', 'agencies.id', '=', 'tenants.agency_id')
            ->join('tenants', 'unsolds.tenant_id', '=', 'tenants.id')
            ->where('unsolds.id', $this->unsold_id)
            ->select('agencies.*');
    }
}

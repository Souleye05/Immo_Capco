<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'agency_id',
        'flat_id',
        'contract_id',
        'tenant_id',
        'numero',
        'type',
        'current_month',
        'amount',
        'status',
        'date_payment',
    ];

    protected $casts = [
        'type' => PaymentType::class,

    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // Relation pour le tenant scoping (pluriel)
    public function agencys(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function getTenantAttribute()
    {
        return $this->contract ? $this->contract->tenant : $this->belongsTo(Tenant::class);
    }
    // Définir une méthode pour vérifier si la facture est complète
    public function isComplete(): bool
    {
        return $this->amount_paid >= $this->amount;
    }
    public function getIsFullyPaidAttribute(): bool
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
    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function getTypeBadgeColor(): string
    {
        return match ($this->type) {
            PaymentType::LOYER => 'success',
            PaymentType::CAUTION => 'warning',
            PaymentType::COMMISSION => 'info',
        };
    }

    public function getDisplayLabel(): string
    {
        $label = $this->type->getLabel();

        if ($this->type === PaymentType::LOYER && $this->current_month) {
            $label .= " - {$this->current_month}";
        }

        return $label;
    }

    // Scopes
    public function scopeOfType($query, PaymentType $type)
    {
        return $query->where('type', $type);
    }

    public function scopePaid($query)
    {
        return $query->where('status', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', false);
    }

    public function scopeForTenantAndFlat($query, int $tenantId, int $flatId)
    {
        return $query->where('tenant_id', $tenantId)->where('flat_id', $flatId);
    }
}

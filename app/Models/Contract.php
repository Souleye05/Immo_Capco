<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ContractStatus;
use App\Services\ContractService;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;

class Contract extends Model
{
    use Notifiable;
    protected $fillable = [
        'property_id',
        'flat_id',
        'tenant_id',
        'contract_number',
        'monthly_rent',
        'cautions',
        'start_date',
        'end_date',
        'contract_duration_months',
        'notice_period_days',
        'alert_days_before',
        'auto_renewal',
        'renewal_duration_months',
        'status',
        'notes',
        'conditions',

        
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'cautions' => 'decimal:2',
        'auto_renewal' => 'boolean',
        'conditions' => 'array',
        'status' => ContractStatus::class,
    ];

    // ============================================
    // RELATIONS
    // ============================================

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('status', ContractStatus::ACTIVE);
    }

    public function scopeExpiringSoon($query, $days = 90)
    {
        return $query->active()
            ->where('end_date', '<=', Carbon::now()->addDays($days))
            ->where('end_date', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->active()
            ->where('end_date', '<', Carbon::now());
    }

    public function scopeAutoRenewable($query)
    {
        return $query->active()
            ->where('auto_renewal', true)
            ->where('end_date', '<=', Carbon::now());
    }



    // ============================================
    // ACCESSORS (délégués au service)
    // ============================================

//    public function notifications()
//     {
//         return $this->hasMany(ContractNotification::class);
//     }

    // Accesseur pour les jours avant expiration
    public function getDaysUntilExpirationAttribute()
    {
        return Carbon::now()->diffInDays($this->end_date, false);
    }

    public function getIsExpiringSoonAttribute()
    {
        return $this->days_until_expiration > 0 && $this->days_until_expiration <= $this->alert_days_before;
    }

    public function getIsExpiredAttribute(): bool
    {
        return app(ContractService::class)->isExpired($this);
    }

    // public function getIsExpiringSoonAttribute(): bool
    // {
    //     return app(ContractService::class)->isExpiringSoon($this);
    // }

    public function getTotalDurationAttribute(): int
    {
        return app(ContractService::class)->getTotalDuration($this);
    }

    // ============================================
    // METHODS (délégués au service)
    // ============================================

    public function renew(?int $months = null, ?float $newRent = null): self
    {
        return app(ContractService::class)->renewContract($this, $months, $newRent);
    }

    public function terminate(?string $reason = null): bool
    {
        return app(ContractService::class)->terminateContract($this, $reason);
    }

    public function shouldAlert(): bool
    {
        return app(ContractService::class)->shouldSendAlert($this);
    }

    // ============================================
    // BOOT
    // ============================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = app(ContractService::class)->generateContractNumber();
            }
        });
    }
}
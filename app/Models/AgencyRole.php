<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class AgencyRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'description',
        'permissions',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    // Relations
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // Relation pour le tenant scoping (pluriel)
    public function agencys(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_agency_roles')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    // Méthodes utiles
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function givePermissionTo(string|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $currentPermissions = $this->permissions ?? [];

        $this->permissions = array_unique(array_merge($currentPermissions, $permissions));
        $this->save();

        return $this;
    }

    public function revokePermissionTo(string|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $currentPermissions = $this->permissions ?? [];

        $this->permissions = array_diff($currentPermissions, $permissions);
        $this->save();

        return $this;
    }

    // Auto-generate slug and set created_by
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }

            // Ensure created_by is set if not already provided
            if (empty($role->created_by) && auth()->check()) {
                $role->created_by = auth()->user()->id;
            }
        });
    }
}

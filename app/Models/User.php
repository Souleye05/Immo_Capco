<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class);
    }

    // Alias pour compatibilité avec le code existant
    public function agencys(): BelongsToMany
    {
        return $this->agencies();
    }

    /**
     * Properties owned by this user
     */
    public function ownedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    /**
     * Owner records associated with this user
     */
    public function ownerRecords(): HasMany
    {
        return $this->hasMany(Owner::class);
    }

    /**
     * Tenant records associated with this user
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Contracts where this user is the tenant (through Tenant model)
     */
    public function tenantContracts(): HasManyThrough
    {
        return $this->hasManyThrough(Contract::class, Tenant::class, 'user_id', 'tenant_id');
    }

    public function getTenants(Panel $panel): Collection
    {
        // For super-admin panel, return empty collection (no tenant required)
        if ($panel->getId() === 'super-admin') {
            return collect();
        }

        return $this->agencys;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Super admin a accès à tous les panels
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return match ($panel->getId()) {
            'super-admin' => false, // Seuls les super-admins peuvent accéder (déjà vérifié ci-dessus)
            'admin' => $this->hasRole('agency-owner') ||
                $this->hasRole('admin') ||
                $this->hasPermissionTo('access_admin_panel') ||
                $this->hasPermissionTo('access_admin_panel_limited') ||
                $this->hasAnyAgencyRole(), // Permettre l'accès aux employés avec des rôles d'agence
            'owner' => $this->hasRole('owner') || $this->hasPermissionTo('access_owner_panel') || $this->ownedProperties()->exists(),
            'tenant' => $this->hasRole('tenant') || $this->hasPermissionTo('access_tenant_panel') || $this->tenantContracts()->exists(),
            default => false,
        };
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user can access a specific tenant (agency)
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // Super admin can access any tenant
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user is associated with this agency
        return $this->agencys()->whereKey($tenant)->exists();
    }

    /**
     * Get managed agencies for admin users
     */
    public function managedAgencies(): BelongsToMany
    {
        return $this->agencys()->wherePivot('role', 'manager');
    }

    /**
     * Check if user is an agency owner
     */
    public function isAgencyOwner(): bool
    {
        return $this->hasRole('agency-owner');
    }

    /**
     * Check if user can manage admins (only agency-owners can)
     */
    public function canManageAdmins(): bool
    {
        return $this->hasRole('agency-owner') || $this->hasPermissionTo('manage_agency_admins');
    }

    /**
     * Check if user can assign permissions to admins
     */
    public function canAssignPermissions(): bool
    {
        return $this->hasRole('agency-owner') || $this->hasPermissionTo('assign_admin_permissions');
    }

    /**
     * Agency roles assigned to this user
     */
    public function agencyRoles(): BelongsToMany
    {
        return $this->belongsToMany(AgencyRole::class, 'user_agency_roles')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    /**
     * Get agency roles for a specific agency
     */
    public function agencyRolesForAgency(int $agencyId): Collection
    {
        return $this->agencyRoles()->where('agency_id', $agencyId)->get();
    }

    /**
     * Check if user has a specific agency role
     */
    public function hasAgencyRole(string $roleSlug, int $agencyId): bool
    {
        return $this->agencyRoles()
            ->where('agency_id', $agencyId)
            ->where('slug', $roleSlug)
            ->exists();
    }

    /**
     * Check if user has agency permission
     */
    public function hasAgencyPermission(string $permission, int $agencyId): bool
    {
        $agencyRoles = $this->agencyRolesForAgency($agencyId);

        foreach ($agencyRoles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign agency role to user
     */
    public function assignAgencyRole(AgencyRole $role, User $assignedBy): void
    {
        $this->agencyRoles()->attach($role->id, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Remove agency role from user
     */
    public function removeAgencyRole(AgencyRole $role): void
    {
        $this->agencyRoles()->detach($role->id);
    }

    /**
     * Check if user has any agency role
     */
    public function hasAnyAgencyRole(): bool
    {
        return $this->agencyRoles()->exists();
    }
}

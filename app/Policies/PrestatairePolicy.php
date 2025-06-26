<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Prestataire;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrestatairePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_prestataire');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Prestataire $prestataire): bool
    {
        return $user->can('view_prestataire');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_prestataire');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Prestataire $prestataire): bool
    {
        return $user->can('update_prestataire');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Prestataire $prestataire): bool
    {
        return $user->can('delete_prestataire');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_prestataire');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Prestataire $prestataire): bool
    {
        return $user->can('force_delete_prestataire');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_prestataire');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Prestataire $prestataire): bool
    {
        return $user->can('restore_prestataire');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_prestataire');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Prestataire $prestataire): bool
    {
        return $user->can('replicate_prestataire');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_prestataire');
    }
}

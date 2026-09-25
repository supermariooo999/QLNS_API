<?php

namespace App\Policies;

use App\Models\LuongDuyet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LuongDuyetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LuongDuyet $luongDuyet): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LuongDuyet $luongDuyet): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LuongDuyet $luongDuyet): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LuongDuyet $luongDuyet): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LuongDuyet $luongDuyet): bool
    {
        //
    }
}

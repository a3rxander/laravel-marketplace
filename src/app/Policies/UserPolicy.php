<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, User $targetUser): bool
    {
        // Admins can view any user
        if ($user->is_admin) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $targetUser->id;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, User $targetUser): bool
    {
        // Admins can update any user
        if ($user->is_admin) {
            return true;
        }

        // Users can update their own profile
        return $user->id === $targetUser->id;
    }

    public function delete(User $user, User $targetUser): bool
    {
        // Only admins can delete users
        if (!$user->is_admin) {
            return false;
        }

        // Cannot delete themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Cannot delete other admins (unless super admin logic is implemented)
        return !$targetUser->is_admin;
    }

    public function updateStatus(User $user, User $targetUser): bool
    {
        // Only admins can update user status
        if (!$user->is_admin) {
            return false;
        }

        // Cannot update their own status
        if ($user->id === $targetUser->id) {
            return false;
        }

        return true;
    }

    public function makeAdmin(User $user, User $targetUser): bool
    {
        // Only admins can make other users admin
        if (!$user->is_admin) {
            return false;
        }

        // Cannot modify their own admin status
        if ($user->id === $targetUser->id) {
            return false;
        }

        return true;
    }

    public function viewAdminPanel(User $user): bool
    {
        return $user->is_admin;
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->is_admin;
    }

    public function export(User $user): bool
    {
        return $user->is_admin;
    }

    public function impersonate(User $user, User $targetUser): bool
    {
        // Only admins can impersonate
        if (!$user->is_admin) {
            return false;
        }

        // Cannot impersonate themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Cannot impersonate other admins
        return !$targetUser->is_admin;
    }
}
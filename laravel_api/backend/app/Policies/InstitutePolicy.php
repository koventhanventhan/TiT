<?php

namespace App\Policies;

use App\Models\Institute;
use App\Models\User;

class InstitutePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function view(User $user, Institute $institute): bool
    {
        return $user->role === 'super_admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function update(User $user, Institute $institute): bool
    {
        return $user->role === 'super_admin';
    }

    public function delete(User $user, Institute $institute): bool
    {
        if ($user->role !== 'super_admin') {
            return false;
        }

        if ((int) $institute->id === 1) {
            return false;
        }

        return $institute->canBeDeletedSafely();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function suspend(User $user, Institute $institute): bool
    {
        return $user->role === 'super_admin' && (int) $institute->id !== 1;
    }
}


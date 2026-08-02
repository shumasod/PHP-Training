<?php

namespace App\Policies;

use App\Models\Lodge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LodgePolicy
{
    use HandlesAuthorization;

    public function update(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        // For example, only admins can update lodges
        return $user->isAdmin();
    }
    
    public function delete(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        return $user->isAdmin();
    }
}

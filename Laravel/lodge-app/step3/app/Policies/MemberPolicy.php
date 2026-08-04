<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\Lodge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemberPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function update(User $user, Member $member): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function delete(User $user, Member $member): bool
    {
        // Add your authorization logic here
        return true;
    }
}

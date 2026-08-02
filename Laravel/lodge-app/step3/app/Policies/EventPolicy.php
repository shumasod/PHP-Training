<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Lodge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function update(User $user, Event $event): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function delete(User $user, Event $event): bool
    {
        // Add your authorization logic here
        return true;
    }
}

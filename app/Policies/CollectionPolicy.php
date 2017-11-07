<?php

namespace App\Policies;

use App\Models\Access\User\User;
use App\Models\Glazy\Material\Collection;

use Illuminate\Auth\Access\HandlesAuthorization;

class CollectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function view(User $user, Collection $collection)
    {
        if($collection->is_private) {
            if($user->id === $collection->created_by_user_id) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can create collections.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
    }

    /**
     * Determine whether the user can update the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function update(User $user, Collection $collection)
    {
        return $user->id === $collection->created_by_user_id;
    }

    /**
     * Determine whether the user can delete the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function delete(User $user, Collection $collection)
    {
        return $user->id === $collection->created_by_user_id;
    }


}

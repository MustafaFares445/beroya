<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CarUserAccessService
{
    public function canListUsers(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }

    /**
     * @return Builder<User>
     */
    public function visibleUsersQuery(User $user): Builder
    {
        $query = User::query();

        if ((int) $user->gallery_id !== 0) {
            $query->where('gallery_id', (int) $user->gallery_id);
        }

        return $query;
    }

    public function canCreateUser(User $actor, int $galleryId, int $permissionLevel): bool
    {
        return (int) $actor->permetions_level === 1
            || ((int) $actor->permetions_level === 2
                && (int) $actor->gallery_id === $galleryId
                && $permissionLevel !== 1);
    }

    public function canViewUser(User $actor, User $target): bool
    {
        return $this->canListUsers($actor)
            && ((int) $actor->gallery_id === 0 || (int) $actor->gallery_id === (int) $target->gallery_id);
    }

    public function canUpdateUser(User $actor, User $target): bool
    {
        return $this->canListUsers($actor)
            && ((int) $target->id !== 1 || (int) $actor->permetions_level === 1);
    }

    public function canDeleteUser(User $actor): bool
    {
        return $this->canListUsers($actor);
    }
}

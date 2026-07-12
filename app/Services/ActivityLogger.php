<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(
        ?User $actor,
        string $actionType,
        Model $target,
        array $oldValues = [],
        array $newValues = [],
        ?string $ipAddress = null,
    ): ?ActivityLog {
        if ($actor === null) {
            return null;
        }

        return ActivityLog::query()->create([
            'actor_user_id' => $actor->id,
            'gallery_id' => (int) $actor->gallery_id !== 0 ? (int) $actor->gallery_id : null,
            'action_type' => $actionType,
            'target_type' => class_basename($target),
            'target_id' => $target->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }
}

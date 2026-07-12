<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class WorkflowNotificationService
{
    /**
     * @return Collection<int, User>
     */
    public function managers(): Collection
    {
        return User::query()
            ->whereIn('permetions_level', [1, 2])
            ->where('is_active', true)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function notifyManagers(
        string $category,
        string $event,
        string $title,
        string $body,
        string $entityType,
        int|string $entityId,
        array $meta = [],
    ): void {
        $recipients = $this->managers();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new WorkflowNotification([
            'category' => $category,
            'event' => $event,
            'title' => $title,
            'body' => $body,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
        ]));
    }
}

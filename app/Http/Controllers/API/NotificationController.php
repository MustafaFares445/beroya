<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->get();
        $unreadCount = $notifications->filter(static function ($notification): bool {
            return $notification->read_at === null;
        })->count();

        return ApiResponse::success(
            [
                'notifications' => NotificationResource::collection($notifications)->resolve(),
            ],
            200,
            ['unread_count' => $unreadCount],
        );
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return ApiResponse::success(NotificationResource::make($record->fresh())->resolve());
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->delete();

        return ApiResponse::success(null);
    }
}

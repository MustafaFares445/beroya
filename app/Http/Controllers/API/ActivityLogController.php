<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    public function index(IndexActivityLogRequest $request): JsonResponse
    {
        /** @var User|null $viewer */
        $viewer = $request->user();
        if ($viewer === null || ! in_array((int) $viewer->permetions_level, [1, 2], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $validated = $request->validated();
        $galleryId = isset($validated['gallery_id']) ? (int) $validated['gallery_id'] : null;

        if ((int) $viewer->permetions_level === 2 && (int) $viewer->gallery_id !== 0) {
            $galleryId = (int) $viewer->gallery_id;
        }

        $activityLogs = ActivityLog::query()
            ->with(['actor', 'gallery'])
            ->when(isset($validated['user_id']), static function ($query) use ($validated): void {
                $query->where('actor_user_id', (int) $validated['user_id']);
            })
            ->when($galleryId !== null, static function ($query) use ($galleryId): void {
                $query->where('gallery_id', $galleryId);
            })
            ->when(isset($validated['action_type']), static function ($query) use ($validated): void {
                $query->where('action_type', (string) $validated['action_type']);
            })
            ->when(isset($validated['target_type']), static function ($query) use ($validated): void {
                $query->where('target_type', (string) $validated['target_type']);
            })
            ->when(isset($validated['target_id']), static function ($query) use ($validated): void {
                $query->where('target_id', (int) $validated['target_id']);
            })
            ->when(isset($validated['date']), static function ($query) use ($validated): void {
                $query->whereDate('created_at', (string) $validated['date']);
            })
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(ActivityLogResource::collection($activityLogs)->resolve());
    }
}

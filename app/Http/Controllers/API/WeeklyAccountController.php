<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexWeeklyAccountsRequest;
use App\Http\Resources\AccountResource;
use App\Models\Gallery;
use App\Models\User;
use App\Models\Week;
use App\Services\AccountAdjustmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class WeeklyAccountController extends Controller
{
    public function __construct(private readonly AccountAdjustmentService $accountAdjustmentService)
    {
    }

    public function index(IndexWeeklyAccountsRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $validated = $request->validated();
        $galleryId = (int) $validated['gallery_id'];

        if ($galleryId !== (int) $user->gallery_id) {
            return ApiResponse::failureData('Unauthorized', 403, 'responses.forbidden');
        }

        $week = Week::query()
            ->where('week_num', (int) $validated['week'])
            ->where('year', (string) $validated['year'])
            ->first();

        if ($week === null) {
            return ApiResponse::failureMessage('responses.accounts.week_not_found', 400);
        }

        if ($galleryId !== 0 || (int) $user->permetions_level !== 1) {
            $gallery = Gallery::query()->find($galleryId);

            if ($gallery === null) {
                return ApiResponse::failureData('Gallery not found', 404, 'responses.failure');
            }
        }

        $accounts = $this->accountAdjustmentService->listWeeklyAccounts(
            (int) $validated['week'],
            (string) $validated['year'],
            $galleryId,
            $user,
        );

        if ($accounts->isEmpty()) {
            return ApiResponse::failureData([], 400, 'responses.failure');
        }

        return ApiResponse::success(AccountResource::collection($accounts)->resolve());
    }

    private function canManageAccounts(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

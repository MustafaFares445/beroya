<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBonusRequest;
use App\Http\Requests\UpdateBonusRequest;
use App\Http\Resources\BonusResource;
use App\Models\Account;
use App\Models\Bonus;
use App\Models\User;
use App\Services\AccountAdjustmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function __construct(private readonly AccountAdjustmentService $accountAdjustmentService)
    {
    }

    public function store(StoreBonusRequest $request, Account $account): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureMessage('Unauthorized access', 403);
        }

        $bonus = $this->accountAdjustmentService->storeBonus($account, $request->validated());

        return ApiResponse::success(
            BonusResource::make($bonus)->resolve(),
            200,
            ['message' => 'responses.bonus.created'],
        );
    }

    public function update(UpdateBonusRequest $request, Bonus $bonus): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureMessage('Unauthorized access', 403);
        }

        $updatedBonus = $this->accountAdjustmentService->updateBonus($bonus, $request->validated());

        return ApiResponse::success(
            BonusResource::make($updatedBonus)->resolve(),
            200,
            ['message' => 'responses.bonus.updated'],
        );
    }

    public function destroy(Request $request, Bonus $bonus): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureMessage('Unauthorized access', 403);
        }

        $this->accountAdjustmentService->deleteBonus($bonus);

        return ApiResponse::success(null, 200, ['message' => 'responses.bonus.deleted']);
    }

    private function canManageAccounts(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

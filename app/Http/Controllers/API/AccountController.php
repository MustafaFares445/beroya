<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountAdjustmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private readonly AccountAdjustmentService $accountAdjustmentService)
    {
    }

    public function show(Request $request, Account $account): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        return ApiResponse::success(AccountResource::make($account)->resolve());
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updated = $this->accountAdjustmentService->updateAccount($account, $request->validated());

        if (! $updated) {
            return ApiResponse::failureMessage('responses.accounts.update_failed', 400);
        }

        return ApiResponse::success();
    }

    private function canManageAccounts(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

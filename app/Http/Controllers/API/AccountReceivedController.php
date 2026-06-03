<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountReceivedRequest;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountAdjustmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AccountReceivedController extends Controller
{
    public function __construct(private readonly AccountAdjustmentService $accountAdjustmentService)
    {
    }

    public function __invoke(UpdateAccountReceivedRequest $request, Account $account): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $validated = $request->validated();
        $targetAccount = $account;

        if (isset($validated['user_id'], $validated['week_id'])) {
            $legacyAccount = $this->accountAdjustmentService->findAccountByLegacyIdentifiers(
                null,
                (int) $validated['user_id'],
                (int) $validated['week_id'],
            );

            if ($legacyAccount !== null) {
                $targetAccount = $legacyAccount;
            }
        }

        $updated = $this->accountAdjustmentService->updateReceived($targetAccount, $validated);

        if (! $updated) {
            return ApiResponse::failureMessage('responses.accounts.received_update_failed', 400);
        }

        return ApiResponse::success();
    }

    private function canManageAccounts(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountAdjustmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDetailsController extends Controller
{
    public function __construct(private readonly AccountAdjustmentService $accountAdjustmentService)
    {
    }

    public function __invoke(Request $request, Account $account): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureData('Unauthorized', 403, 'responses.forbidden');
        }

        $details = $this->accountAdjustmentService->accountDetails($account);

        return ApiResponse::success($details->all());
    }

    private function canManageAccounts(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

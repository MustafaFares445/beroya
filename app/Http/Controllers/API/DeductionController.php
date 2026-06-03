<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeductionRequest;
use App\Http\Requests\UpdateDeductionRequest;
use App\Http\Resources\DeductionResource;
use App\Models\Account;
use App\Models\Deduction;
use App\Models\User;
use App\Services\AccountAdjustmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    public function __construct(private readonly AccountAdjustmentService $accountAdjustmentService)
    {
    }

    public function store(StoreDeductionRequest $request, Account $account): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureMessage('Unauthorized access', 403);
        }

        $deduction = $this->accountAdjustmentService->storeDeduction($account, $request->validated());

        return ApiResponse::success(
            DeductionResource::make($deduction)->resolve(),
            200,
            ['message' => 'responses.deduction.created'],
        );
    }

    public function update(UpdateDeductionRequest $request, Deduction $deduction): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureMessage('Unauthorized access', 403);
        }

        $updatedDeduction = $this->accountAdjustmentService->updateDeduction($deduction, $request->validated());

        return ApiResponse::success(
            DeductionResource::make($updatedDeduction)->resolve(),
            200,
            ['message' => 'responses.deduction.updated'],
        );
    }

    public function destroy(Request $request, Deduction $deduction): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageAccounts($user)) {
            return ApiResponse::failureMessage('Unauthorized access', 403);
        }

        $this->accountAdjustmentService->deleteDeduction($deduction);

        return ApiResponse::success(null, 200, ['message' => 'responses.deduction.deleted']);
    }

    private function canManageAccounts(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

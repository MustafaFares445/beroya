<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserPasswordController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function __invoke(UpdateUserPasswordRequest $request, User $user): JsonResponse
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! in_array((int) $authenticatedUser->permetions_level, [1, 2, 3, 4], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        if ((int) $authenticatedUser->id !== (int) $user->id) {
            return ApiResponse::failureData('Invalid ID', 422, 'responses.user.invalid_id');
        }

        $payloadId = $request->validated('id');
        if ($payloadId !== null && (int) $payloadId !== (int) $user->id) {
            return ApiResponse::failureData('Invalid ID', 422, 'responses.user.invalid_id');
        }

        $updated = $this->userService->updatePassword(
            $user,
            (string) $request->validated('old_password'),
            (string) $request->validated('new_password'),
        );

        if (! $updated) {
            return ApiResponse::failureData('Old password is incorrect', 422, 'responses.user.old_password_incorrect');
        }

        return ApiResponse::success(null, 200, ['message' => 'responses.user.password_updated']);
    }
}

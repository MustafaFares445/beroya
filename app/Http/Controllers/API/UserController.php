<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! in_array((int) $authenticatedUser->permetions_level, [1, 2], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $usersQuery = User::query()->with('realEstateOffice.province');
        if ((int) $authenticatedUser->gallery_id !== 0) {
            $usersQuery->where('gallery_id', (int) $authenticatedUser->gallery_id);
        }

        $users = $usersQuery->get();

        return ApiResponse::success(UserResource::collection($users)->resolve());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        $payload = $request->validated();
        $targetGalleryId = (int) $payload['gallery_id'];
        $targetPermission = (int) $payload['permetions_level'];

        $canCreate =
            (int) $authenticatedUser->permetions_level === 1 ||
            (
                (int) $authenticatedUser->permetions_level === 2 &&
                (int) $authenticatedUser->gallery_id === $targetGalleryId &&
                $targetPermission !== 1
            );

        if (! $canCreate) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $createdUser = $this->userService->store($payload);

        return ApiResponse::success(UserResource::make($createdUser->loadMissing('realEstateOffice.province'))->resolve());
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! in_array((int) $authenticatedUser->permetions_level, [1, 2], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        if ((int) $authenticatedUser->gallery_id !== 0 && (int) $authenticatedUser->gallery_id !== (int) $user->gallery_id) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        return ApiResponse::success(UserResource::make($user->loadMissing('realEstateOffice.province'))->resolve());
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! in_array((int) $authenticatedUser->permetions_level, [1, 2], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        if ((int) $user->id === 1 && (int) $authenticatedUser->permetions_level !== 1) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedUser = $this->userService->update($user, $request->validated());

        return ApiResponse::success(UserResource::make($updatedUser->loadMissing('realEstateOffice.province'))->resolve());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! in_array((int) $authenticatedUser->permetions_level, [1, 2], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        if ((int) $authenticatedUser->id === (int) $user->id) {
            return ApiResponse::failureMessage('responses.user.self_delete_forbidden', 400);
        }

        $this->userService->delete($user);

        return ApiResponse::success(null, 200, ['message' => 'responses.user.delete_success']);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RealEstateAccessService;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RealEstateAccessService $realEstateAccessService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! $this->realEstateAccessService->canListUsers($authenticatedUser)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $users = $this->realEstateAccessService
            ->visibleUsersQuery($authenticatedUser)
            ->with(['realEstateProvince', 'realEstateOffice.province'])
            ->get();

        return ApiResponse::success(UserResource::collection($users)->resolve());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        $payload = $request->validated();
        $targetPermission = (int) $payload['permetions_level'];
        $provinceId = isset($payload['real_estate_province_id']) ? (int) $payload['real_estate_province_id'] : null;
        $officeId = isset($payload['real_estate_office_id']) ? (int) $payload['real_estate_office_id'] : null;

        if (! $this->realEstateAccessService->canCreateUser(
            $authenticatedUser,
            $targetPermission,
            $provinceId,
            $officeId
        )) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $createdUser = $this->userService->store($payload);

        return ApiResponse::success(UserResource::make(
            $createdUser->loadMissing(['realEstateProvince', 'realEstateOffice.province'])
        )->resolve());
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! $this->realEstateAccessService->canViewUser($authenticatedUser, $user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        return ApiResponse::success(UserResource::make(
            $user->loadMissing(['realEstateProvince', 'realEstateOffice.province'])
        )->resolve());
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        $payload = $request->validated();
        $targetPermission = (int) $payload['permetions_level'];
        $officeId = isset($payload['real_estate_office_id']) ? (int) $payload['real_estate_office_id'] : null;

        if (! $this->realEstateAccessService->canUpdateUser(
            $authenticatedUser,
            $user,
            $targetPermission,
            $officeId
        )) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedUser = $this->userService->update($user, $payload);

        return ApiResponse::success(UserResource::make(
            $updatedUser->loadMissing(['realEstateProvince', 'realEstateOffice.province'])
        )->resolve());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        if (! $this->realEstateAccessService->canDeleteUser($authenticatedUser, $user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        if ((int) $authenticatedUser->id === (int) $user->id) {
            return ApiResponse::failureMessage('responses.user.self_delete_forbidden', 400);
        }

        $this->userService->delete($user);

        return ApiResponse::success(null, 200, ['message' => 'responses.user.delete_success']);
    }
}

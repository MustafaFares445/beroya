<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CarUserAccessService;
use App\Services\RealEstateAccessService;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RealEstateAccessService $realEstateAccessService,
        private readonly CarUserAccessService $carUserAccessService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if ($authenticatedUser === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        $usesRealEstatePermissions = $authenticatedUser->isRealEstateUser();
        $canListUsers = $usesRealEstatePermissions
            ? $this->realEstateAccessService->canListUsers($authenticatedUser)
            : $this->carUserAccessService->canListUsers($authenticatedUser);

        if (! $canListUsers) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $usersQuery = $usesRealEstatePermissions
            ? $this->realEstateAccessService->visibleUsersQuery($authenticatedUser)
            : $this->carUserAccessService->visibleUsersQuery($authenticatedUser);

        $users = $usersQuery
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
        $usesRealEstatePermissions = $authenticatedUser->isRealEstateUser();
        $provinceId = isset($payload['real_estate_province_id']) ? (int) $payload['real_estate_province_id'] : null;
        $officeId = isset($payload['real_estate_office_id']) ? (int) $payload['real_estate_office_id'] : null;

        $canCreateUser = $usesRealEstatePermissions
            ? $this->realEstateAccessService->canCreateUser(
                $authenticatedUser,
                $targetPermission,
                $provinceId,
                $officeId
            )
            : $this->carUserAccessService->canCreateUser(
                $authenticatedUser,
                (int) $payload['gallery_id'],
                $targetPermission
            );

        if (! $canCreateUser) {
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

        $usesRealEstatePermissions = $authenticatedUser->isRealEstateUser();
        $canViewUser = $usesRealEstatePermissions
            ? $this->realEstateAccessService->canViewUser($authenticatedUser, $user)
            : $this->carUserAccessService->canViewUser($authenticatedUser, $user);

        if (! $canViewUser) {
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
        $usesRealEstatePermissions = $authenticatedUser->isRealEstateUser();
        $officeId = isset($payload['real_estate_office_id']) ? (int) $payload['real_estate_office_id'] : null;

        $canUpdateUser = $usesRealEstatePermissions
            ? $this->realEstateAccessService->canUpdateUser(
                $authenticatedUser,
                $user,
                $targetPermission,
                $officeId
            )
            : $this->carUserAccessService->canUpdateUser($authenticatedUser, $user);

        if (! $canUpdateUser) {
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

        $usesRealEstatePermissions = $authenticatedUser->isRealEstateUser();
        $canDeleteUser = $usesRealEstatePermissions
            ? $this->realEstateAccessService->canDeleteUser($authenticatedUser, $user)
            : $this->carUserAccessService->canDeleteUser($authenticatedUser);

        if (! $canDeleteUser) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        if ((int) $authenticatedUser->id === (int) $user->id) {
            return ApiResponse::failureMessage('responses.user.self_delete_forbidden', 400);
        }

        $this->userService->delete($user);

        return ApiResponse::success(null, 200, ['message' => 'responses.user.delete_success']);
    }
}

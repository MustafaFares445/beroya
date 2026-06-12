<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated(), $request->ip() ?? 'UNKNOWN');

        if (! ($result['success'] ?? false)) {
            $messageKey = (string) ($result['message'] ?? 'responses.auth.login_failed');
            $statusCode = $messageKey === 'responses.auth.login_too_many_attempts' ? 429 : 200;

            return ApiResponse::failureMessage($messageKey, $statusCode);
        }

        /** @var User $user */
        $user = $result['user'];
        $token = (string) $result['token'];
        /** @var Carbon $tokenExpiry */
        $tokenExpiry = $result['token_expiry'];
        $user->loadMissing('realEstateOffice.province');
        $permissionLevel = $user->permetions_level !== null ? (int) $user->permetions_level : null;

        return ApiResponse::success([
            'id' => $user->id,
            'user_name' => $user->user_name,
            'gallery_id' => $user->gallery_id,
            'real_estate_office_id' => $user->real_estate_office_id,
            'real_estate_office_name' => $user->realEstateOffice?->name,
            'real_estate_province_id' => $user->realEstateOffice?->province?->id,
            'real_estate_province_name' => $user->realEstateOffice?->province?->name,
            'real_estate_role' => $user->real_estate_role,
            'real_estate_role_label' => RealEstate::roleLabel($user->real_estate_role, $permissionLevel),
            'permetions_level' => $user->permetions_level,
            'salary' => $user->salary,
            'phone' => $user->phone,
        ], 200, [
            'token' => $token,
            'token_expiry' => $tokenExpiry->format('Y-m-d H:i:s'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::failureMessage('responses.auth.token_invalid', 401);
        }

        $this->authService->logout($user);

        return ApiResponse::success(null, 200, [
            'message' => 'responses.auth.logout_success',
        ]);
    }
}

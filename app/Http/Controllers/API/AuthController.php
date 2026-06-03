<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated(), $request->ip() ?? 'UNKNOWN');

        if (! ($result['success'] ?? false)) {
            $messageKey = (string) ($result['message'] ?? 'responses.auth.login_failed');
            $statusCode = $messageKey === 'responses.auth.login_too_many_attempts' ? 429 : 200;

            return ApiResponse::failureMessage($messageKey, $statusCode);
        }

        /** @var \App\Models\User $user */
        $user = $result['user'];
        $token = (string) $result['token'];
        /** @var \Illuminate\Support\Carbon $tokenExpiry */
        $tokenExpiry = $result['token_expiry'];

        return ApiResponse::success([
            'id' => $user->id,
            'user_name' => $user->user_name,
            'gallery_id' => $user->gallery_id,
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

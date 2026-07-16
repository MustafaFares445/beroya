<?php

namespace App\Services;

use App\Models\User;
use App\Support\Auth\LegacyPasswordFallback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    public function __construct(private readonly AccountService $accountService) {}

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, message?: string, user?: User, token?: string, token_expiry?: Carbon}
     */
    public function login(array $credentials, string $ipAddress): array
    {
        $limiterKey = sprintf('login:%s', sha1($ipAddress));
        $maxAttempts = 5;
        $decaySeconds = 900;

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return [
                'success' => false,
                'message' => 'responses.auth.login_too_many_attempts',
            ];
        }

        $userName = (string) ($credentials['username'] ?? '');
        $password = (string) ($credentials['password'] ?? '');

        $user = User::query()->where('user_name', $userName)->first();
        if ($user === null) {
            RateLimiter::hit($limiterKey, $decaySeconds);

            return [
                'success' => false,
                'message' => 'responses.auth.user_not_found',
            ];
        }

        if (! LegacyPasswordFallback::verify($password, $user->password)) {
            RateLimiter::hit($limiterKey, $decaySeconds);

            return [
                'success' => false,
                'message' => 'responses.auth.password_incorrect',
            ];
        }

        RateLimiter::clear($limiterKey);

        if (LegacyPasswordFallback::needsRehash($password, $user->password)) {
            $user->password = Hash::make($password);
        }

        $tokenExpiry = Carbon::now()->addHours((int) config('auth.api_token_ttl_hours', 8));
        $user->last_login = Carbon::now();
        $user->save();

        $accessToken = $user->createToken('api-token', ['*'], $tokenExpiry);

        $weekNumber = isset($credentials['currentWeekNum']) ? (int) $credentials['currentWeekNum'] : null;
        $year = isset($credentials['currentYear']) ? (int) $credentials['currentYear'] : null;

        if ($weekNumber !== null && $weekNumber > 0 && $year !== null && $year > 0) {
            $week = $this->accountService->ensureWeekExists($weekNumber, $year);
            $this->accountService->ensureAccountsForWeekExists($week);
        }

        return [
            'success' => true,
            'user' => $user->loadMissing(['realEstateProvince', 'realEstateOffice.province']),
            'token' => $accessToken->plainTextToken,
            'token_expiry' => $tokenExpiry,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}

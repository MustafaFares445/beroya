<?php

namespace App\Services;

use App\Models\User;
use App\Support\Auth\LegacyPasswordFallback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private readonly AccountService $accountService)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): User
    {
        return DB::transaction(static function () use ($payload): User {
            return User::query()->create([
                'user_name' => (string) $payload['user_name'],
                'password' => Hash::make((string) $payload['password']),
                'gallery_id' => (int) $payload['gallery_id'],
                'permetions_level' => (int) $payload['permetions_level'],
                'salary' => (int) $payload['salary'],
                'phone' => (string) $payload['phone'],
                'is_active' => true,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(User $user, array $payload): User
    {
        return DB::transaction(function () use ($user, $payload): User {
            $data = [
                'user_name' => (string) $payload['user_name'],
                'gallery_id' => (int) $payload['gallery_id'],
                'permetions_level' => (int) $payload['permetions_level'],
                'salary' => (int) $payload['salary'],
                'phone' => (string) $payload['phone'],
            ];

            if (isset($payload['password']) && (string) $payload['password'] !== '') {
                $data['password'] = Hash::make((string) $payload['password']);
            }

            $user->update($data);

            $this->accountService->recalculateUserAccounts($user->id);

            return $user->fresh();
        });
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function updatePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (! LegacyPasswordFallback::verify($oldPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }
}

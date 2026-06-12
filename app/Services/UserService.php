<?php

namespace App\Services;

use App\Models\User;
use App\Support\Auth\LegacyPasswordFallback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private readonly AccountService $accountService) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): User
    {
        return DB::transaction(function () use ($payload): User {
            $user = User::query()->create([
                'user_name' => (string) $payload['user_name'],
                'password' => Hash::make((string) $payload['password']),
                'gallery_id' => (int) $payload['gallery_id'],
                'real_estate_office_id' => $this->nullableInteger($payload, 'real_estate_office_id'),
                'real_estate_role' => $this->nullableString($payload, 'real_estate_role'),
                'permetions_level' => (int) $payload['permetions_level'],
                'salary' => (int) $payload['salary'],
                'phone' => (string) $payload['phone'],
                'is_active' => true,
            ]);

            return $user->fresh(['realEstateOffice.province']) ?? $user;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(User $user, array $payload): User
    {
        return DB::transaction(function () use ($user, $payload): User {
            $user->fill([
                'user_name' => (string) $payload['user_name'],
                'gallery_id' => (int) $payload['gallery_id'],
                'permetions_level' => (int) $payload['permetions_level'],
                'salary' => (int) $payload['salary'],
                'phone' => (string) $payload['phone'],
            ]);

            if (array_key_exists('password', $payload) && $payload['password'] !== null && $payload['password'] !== '') {
                $user->password = Hash::make((string) $payload['password']);
            }

            if (array_key_exists('real_estate_office_id', $payload)) {
                $user->real_estate_office_id = $this->nullableInteger($payload, 'real_estate_office_id');
            }

            if (array_key_exists('real_estate_role', $payload)) {
                $user->real_estate_role = $this->nullableString($payload, 'real_estate_role');
            }

            $user->save();
            $this->accountService->recalculateUserAccounts((int) $user->id);

            return $user->fresh(['realEstateOffice.province']) ?? $user;
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableInteger(array $payload, string $key): ?int
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        return (int) $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        return (string) $payload[$key];
    }
}

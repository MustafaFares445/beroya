<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminBootstrapService
{
    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function bootstrap(): array
    {
        if (app()->isProduction() || ! (bool) config('legacy.allow_admin_init', false)) {
            return [
                'success' => false,
                'message' => 'responses.admin.bootstrap_disabled',
            ];
        }

        if (User::query()->exists()) {
            return [
                'success' => false,
                'message' => 'responses.admin.users_exist',
            ];
        }

        $adminPassword = bin2hex(random_bytes(8));
        $financialPassword = bin2hex(random_bytes(8));
        $guestPassword = bin2hex(random_bytes(8));

        DB::transaction(static function () use ($adminPassword, $financialPassword, $guestPassword): void {
            User::query()->create([
                'user_name' => 'admin',
                'password' => Hash::make($adminPassword),
                'gallery_id' => 0,
                'permetions_level' => 1,
                'salary' => 0,
                'phone' => '',
                'is_active' => true,
            ]);

            User::query()->create([
                'user_name' => 'المدير المالي',
                'password' => Hash::make($financialPassword),
                'gallery_id' => 0,
                'permetions_level' => 1,
                'salary' => 0,
                'phone' => '',
                'is_active' => true,
            ]);

            User::query()->create([
                'user_name' => 'guest',
                'password' => Hash::make($guestPassword),
                'gallery_id' => 0,
                'permetions_level' => 111,
                'salary' => 0,
                'phone' => '',
                'is_active' => true,
            ]);
        });

        return [
            'success' => true,
            'data' => [
                'admin' => [
                    'user_name' => 'admin',
                    'password' => $adminPassword,
                    'note' => 'Save the temporary password and change it after first login.',
                ],
                'financial' => [
                    'user_name' => 'المدير المالي',
                    'password' => $financialPassword,
                ],
                'guest' => [
                    'user_name' => 'guest',
                    'password' => $guestPassword,
                ],
            ],
        ];
    }
}

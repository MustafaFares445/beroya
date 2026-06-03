<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsSanctum(User $user, string $tokenName = 'test-token'): string
    {
        $token = $user->createToken($tokenName, ['*'], Carbon::now()->addHours(8))->plainTextToken;

        $this->withToken($token);

        return $token;
    }
}

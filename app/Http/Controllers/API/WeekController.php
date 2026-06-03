<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\WeekResource;
use App\Models\User;
use App\Models\Week;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeekController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->canReadSales($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $weeks = Week::query()
            ->orderByDesc('year')
            ->orderByDesc('week_num')
            ->get();

        return ApiResponse::success(WeekResource::collection($weeks)->resolve());
    }

    private function canReadSales(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2, 3, 4], true);
    }
}

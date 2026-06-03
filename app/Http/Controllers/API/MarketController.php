<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarketRequest;
use App\Http\Requests\UpdateMarketRequest;
use App\Http\Resources\MarketResource;
use App\Models\Market;
use App\Models\User;
use App\Services\MarketService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function __construct(private readonly MarketService $marketService)
    {
    }

    public function index(): JsonResponse
    {
        $markets = Market::query()->get();

        return ApiResponse::success(MarketResource::collection($markets)->resolve());
    }

    public function store(StoreMarketRequest $request): JsonResponse
    {
        if (!$this->canCreateMarket($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $market = $this->marketService->store($request->validated());

        return ApiResponse::success(MarketResource::make($market)->resolve());
    }

    public function show(Market $market): JsonResponse
    {
        return ApiResponse::success(MarketResource::make($market)->resolve());
    }

    public function update(UpdateMarketRequest $request, Market $market): JsonResponse
    {
        if (!$this->canManageMarket($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedMarket = $this->marketService->update($market, $request->validated());

        return ApiResponse::success(MarketResource::make($updatedMarket)->resolve());
    }

    public function destroy(Request $request, Market $market): JsonResponse
    {
        if (!$this->canManageMarket($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $marketData = MarketResource::make($market)->resolve();
        $this->marketService->delete($market);

        return ApiResponse::success($marketData);
    }

    private function canCreateMarket(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2, 3], true);
    }

    private function canManageMarket(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}


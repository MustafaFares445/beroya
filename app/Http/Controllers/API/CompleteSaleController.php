<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CompleteSaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function __invoke(CompleteSaleRequest $request, Sale $sale): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canManageSales($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedSale = $this->saleService->complete($sale, $request->validated());

        return ApiResponse::success(
            SaleResource::make($updatedSale)->resolve(),
            200,
            ['message' => 'responses.sales.completed'],
        );
    }

    private function canManageSales(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

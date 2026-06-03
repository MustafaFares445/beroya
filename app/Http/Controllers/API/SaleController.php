<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexSalesRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function index(IndexSalesRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canReadSales($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $validated = $request->validated();
        $galleryId = isset($validated['gallery_id']) ? (int) $validated['gallery_id'] : (int) $user->gallery_id;

        $sales = $this->saleService->list(
            (string) $validated['status'],
            $galleryId,
            $user,
        );

        return ApiResponse::success(SaleResource::collection($sales)->resolve());
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canReadSales($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $sale = $this->saleService->store($request->validated());

        return ApiResponse::success(
            SaleResource::make($sale)->resolve(),
            200,
            ['message' => 'responses.sales.created'],
        );
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canReadSales($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        return ApiResponse::success(SaleResource::make($sale)->resolve());
    }

    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $this->canReadSales($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedSale = $this->saleService->update($sale, $request->validated());

        return ApiResponse::success(
            SaleResource::make($updatedSale)->resolve(),
            200,
            ['message' => 'responses.sales.updated'],
        );
    }

    private function canReadSales(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2, 3, 4], true);
    }
}

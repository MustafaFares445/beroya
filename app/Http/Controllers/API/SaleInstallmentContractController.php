<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertSaleInstallmentContractRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SaleInstallmentContractController extends Controller
{
    public function __construct(private readonly SaleService $saleService) {}

    public function __invoke(UpsertSaleInstallmentContractRequest $request, Sale $sale): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null || ! $this->canManageSales($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedSale = $this->saleService->updateInstallmentContract(
            $sale,
            $request->validated(),
            $user,
            $request->ip(),
        );

        return ApiResponse::success(
            SaleResource::make($updatedSale->fresh())->resolve(),
            200,
            ['message' => 'responses.sales.updated'],
        );
    }

    private function canManageSales(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}

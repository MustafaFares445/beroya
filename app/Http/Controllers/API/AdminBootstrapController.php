<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdminBootstrapService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminBootstrapController extends Controller
{
    public function __construct(private readonly AdminBootstrapService $adminBootstrapService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $result = $this->adminBootstrapService->bootstrap();

        if (! ($result['success'] ?? false)) {
            return ApiResponse::failureMessage(
                (string) ($result['message'] ?? 'responses.admin.bootstrap_failed'),
                400,
            );
        }

        return ApiResponse::success(
            $result['data'] ?? null,
            200,
            ['message' => 'responses.admin.bootstrap_success'],
        );
    }
}

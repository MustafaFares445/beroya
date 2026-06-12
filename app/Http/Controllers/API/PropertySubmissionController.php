<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPropertySubmissionRequest;
use App\Http\Requests\StorePropertySubmissionRequest;
use App\Http\Resources\PropertySubmissionResource;
use App\Models\PropertySubmission;
use App\Models\User;
use App\Services\PropertySubmissionService;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertySubmissionController extends Controller
{
    public function __construct(private readonly PropertySubmissionService $propertySubmissionService) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->canReviewPropertySubmissions($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $submissions = $this->propertySubmissionService->list();

        return ApiResponse::success(PropertySubmissionResource::collection($submissions)->resolve());
    }

    public function show(Request $request, PropertySubmission $submission): JsonResponse
    {
        if (! $this->canReviewPropertySubmissions($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $submission->loadMissing([
            'province',
            'office.province',
            'mainCategory',
            'subcategory',
            'publishedProperty',
        ]);

        return ApiResponse::success(PropertySubmissionResource::make($submission)->resolve());
    }

    public function store(StorePropertySubmissionRequest $request): JsonResponse
    {
        $submission = $this->propertySubmissionService->store($request->validated());

        return ApiResponse::success(PropertySubmissionResource::make($submission)->resolve());
    }

    public function approve(Request $request, PropertySubmission $submission): JsonResponse
    {
        if (! $this->canReviewPropertySubmissions($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        /** @var User|null $user */
        $user = $request->user();

        $approvedSubmission = $this->propertySubmissionService->approve($submission, $user);

        return ApiResponse::success(PropertySubmissionResource::make($approvedSubmission)->resolve());
    }

    public function reject(RejectPropertySubmissionRequest $request, PropertySubmission $submission): JsonResponse
    {
        if (! $this->canReviewPropertySubmissions($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $rejectedSubmission = $this->propertySubmissionService->reject($submission, $request->validated());

        return ApiResponse::success(PropertySubmissionResource::make($rejectedSubmission)->resolve());
    }

    private function canReviewPropertySubmissions(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canReviewPropertySubmissions($user);
    }
}

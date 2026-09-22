<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(Request $request, int $hotelId): JsonResponse
    {
        $reviews = $this->reviewService->listByHotel($hotelId, (int) $request->query('per_page', 10));

        return response()->json(ApiFormatter::createJson('Reviews retrieved successfully', [
            'reviews' => ReviewResource::collection($reviews->items()),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]));
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->create($request->validated(), $request->user());

        return response()->json(ApiFormatter::createJson('Review created successfully', [
            'review' => new ReviewResource($review),
        ]), 201);
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $updated = $this->reviewService->update($review, $request->validated(), $request->user());

        return response()->json(ApiFormatter::createJson('Review updated successfully', [
            'review' => new ReviewResource($updated),
        ]));
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        $this->reviewService->delete($review, $request->user());

        return response()->json(ApiFormatter::createJson('Review deleted successfully'));
    }
}

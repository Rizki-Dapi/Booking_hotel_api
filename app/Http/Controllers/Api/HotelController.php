<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Services\HotelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function __construct(private readonly HotelService $hotelService) {}

    public function index(Request $request): JsonResponse
    {
        $hotels = $this->hotelService->list(
            filters: $request->only(['city', 'name', 'min_star_rating']),
            perPage: (int) $request->query('per_page', 10),
        );

        return response()->json(ApiFormatter::createJson('Hotels retrieved successfully', [
            'hotels' => HotelResource::collection($hotels->items()),
            'pagination' => [
                'current_page' => $hotels->currentPage(),
                'last_page' => $hotels->lastPage(),
                'per_page' => $hotels->perPage(),
                'total' => $hotels->total(),
            ],
        ]));
    }

    public function show(int $id): JsonResponse
    {
        $hotel = $this->hotelService->find($id);

        return response()->json(ApiFormatter::createJson('Hotel retrieved successfully', [
            'hotel' => new HotelResource($hotel),
        ]));
    }

    public function store(StoreHotelRequest $request): JsonResponse
    {
        $hotel = $this->hotelService->create($request->validated());

        return response()->json(ApiFormatter::createJson('Hotel created successfully', [
            'hotel' => new HotelResource($hotel),
        ]), 201);
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel): JsonResponse
    {
        $updated = $this->hotelService->update($hotel, $request->validated());

        return response()->json(ApiFormatter::createJson('Hotel updated successfully', [
            'hotel' => new HotelResource($updated),
        ]));
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $this->hotelService->delete($hotel);

        return response()->json(ApiFormatter::createJson('Hotel deleted successfully'));
    }

    public function recommended(Request $request): JsonResponse
    {
        $hotels = $this->hotelService->recommended((int) $request->query('limit', 5));

        return response()->json(ApiFormatter::createJson('Recommended hotels retrieved successfully', [
            'hotels' => HotelResource::collection($hotels),
        ]));
    }
}

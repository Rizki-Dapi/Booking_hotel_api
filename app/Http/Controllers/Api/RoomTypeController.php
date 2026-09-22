<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomTypeRequest;
use App\Http\Requests\UpdateRoomTypeRequest;
use App\Http\Resources\RoomTypeResource;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function __construct(private readonly RoomTypeService $roomTypeService) {}

    public function index(Request $request): JsonResponse
    {
        $roomTypes = $this->roomTypeService->listByHotel(
            (int) $request->query('hotel_id'),
            $request->query('search'),
        );

        return response()->json(ApiFormatter::createJson('Room types retrieved successfully', [
            'room_types' => RoomTypeResource::collection($roomTypes),
        ]));
    }

    public function show(int $id): JsonResponse
    {
        $roomType = $this->roomTypeService->find($id);

        return response()->json(ApiFormatter::createJson('Room type retrieved successfully', [
            'room_type' => new RoomTypeResource($roomType),
        ]));
    }

    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $roomType = $this->roomTypeService->create($request->validated());

        return response()->json(ApiFormatter::createJson('Room type created successfully', [
            'room_type' => new RoomTypeResource($roomType),
        ]), 201);
    }

    public function update(UpdateRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        $updated = $this->roomTypeService->update($roomType, $request->validated());

        return response()->json(ApiFormatter::createJson('Room type updated successfully', [
            'room_type' => new RoomTypeResource($updated),
        ]));
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $this->roomTypeService->delete($roomType);

        return response()->json(ApiFormatter::createJson('Room type deleted successfully'));
    }
}

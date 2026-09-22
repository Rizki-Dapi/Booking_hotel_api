<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private readonly RoomService $roomService) {}

    public function index(Request $request): JsonResponse
    {
        $roomTypeId = (int) $request->query('room_type_id');

        if ($request->filled('check_in_date') && $request->filled('check_out_date')) {
            $rooms = $this->roomService->findAvailable(
                $roomTypeId,
                $request->query('check_in_date'),
                $request->query('check_out_date'),
            );
        } else {

            $rooms = $this->roomService->listByRoomType(
                $roomTypeId,
                $request->query('search'),
                $request->query('occupancy_date', now()->toDateString()),
            );
        }

        return response()->json(ApiFormatter::createJson('Rooms retrieved successfully', [
            'rooms' => RoomResource::collection($rooms),
        ]));
    }

    public function show(int $id): JsonResponse
    {
        $room = $this->roomService->find($id);

        return response()->json(ApiFormatter::createJson('Room retrieved successfully', [
            'room' => new RoomResource($room),
        ]));
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->validated());

        return response()->json(ApiFormatter::createJson('Room created successfully', [
            'room' => new RoomResource($room),
        ]), 201);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $updated = $this->roomService->update($room, $request->validated());

        return response()->json(ApiFormatter::createJson('Room updated successfully', [
            'room' => new RoomResource($updated),
        ]));
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->roomService->delete($room);

        return response()->json(ApiFormatter::createJson('Room deleted successfully'));
    }
}

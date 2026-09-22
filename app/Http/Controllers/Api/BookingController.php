<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->listActiveForUser(
            $request->user(),
            (int) $request->query('per_page', 10),
        );

        return $this->paginatedBookingResponse($bookings, 'Active bookings retrieved successfully');
    }

    public function history(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->listHistoryForUser(
            $request->user(),
            (int) $request->query('per_page', 10),
        );

        return $this->paginatedBookingResponse($bookings, 'Booking history retrieved successfully');
    }

    private function paginatedBookingResponse(LengthAwarePaginator $bookings, string $message): JsonResponse
    {
        $bookings->getCollection()->loadMissing(['room.roomType.hotel', 'payment']);

        return response()->json(ApiFormatter::createJson($message, [
            'bookings' => BookingResource::collection($bookings->items()),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]));
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->createBooking($request->validated(), $request->user());
        $result['booking']->loadMissing(['room.roomType.hotel', 'payment']);

        return response()->json(ApiFormatter::createJson('Booking created successfully', [
            'booking' => new BookingResource($result['booking']),
            'snap_token' => $result['snap_token'],
        ]), 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->loadMissing(['room.roomType.hotel', 'payment']);

        return response()->json(ApiFormatter::createJson('Booking retrieved successfully', [
            'booking' => new BookingResource($booking),
        ]));
    }

    public function cancel(Booking $booking): JsonResponse
    {
        $cancelled = $this->bookingService->cancel($booking);
        $cancelled->loadMissing(['room.roomType.hotel', 'payment']);

        return response()->json(ApiFormatter::createJson('Booking cancelled successfully', [
            'booking' => new BookingResource($cancelled),
        ]));
    }

    public function reschedule(RescheduleBookingRequest $request, Booking $booking): JsonResponse
    {
        $result = $this->bookingService->reschedule($booking, $request->validated());
        $result['booking']->loadMissing(['room.roomType.hotel', 'payment']);

        return response()->json(ApiFormatter::createJson('Booking rescheduled successfully', [
            'booking' => new BookingResource($result['booking']),
            'snap_token' => $result['snap_token'],
        ]));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->listAll(
            search: $request->query('search'),
            status: $request->query('status'),
            perPage: (int) $request->query('per_page', 10),
        );

        return response()->json(ApiFormatter::createJson('Bookings retrieved successfully', [
            'bookings' => BookingResource::collection($bookings->items()),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]));
    }
}

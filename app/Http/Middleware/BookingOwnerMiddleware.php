<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingOwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $booking = $request->route('booking');

        if (! $booking instanceof Booking) {
            $booking = Booking::where('booking_code', $booking)->first();
        }

        if (! $booking) {
            return response()->json(['error' => 'Not Found', 'message' => 'Booking not found'], 404);
        }

        if ($user->hasRole('admin') || $booking->user_id === $user->id) {
            return $next($request);
        }

        return response()->json([
            'error' => 'Forbidden',
            'message' => 'You do not have access to this booking',
        ], 403);
    }
}

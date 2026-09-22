<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RoomStatus::class,
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isAvailableForDateRange(string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        if ($this->status !== RoomStatus::AVAILABLE) {
            return false;
        }

        $query = $this->bookings()
            ->whereNotIn('status', [BookingStatus::CANCELLED->value, BookingStatus::EXPIRED->value])
            ->where('check_in_data', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return ! $query->exists();
    }
}

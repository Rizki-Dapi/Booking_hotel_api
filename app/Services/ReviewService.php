<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\ReviewNotAllowedException;
use App\Helpers\ProfanityFilter;
use App\Models\Review;
use App\Models\User;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws ReviewNotAllowedException
     */
    public function create(array $data, User $user): Review
    {
        $booking = $this->bookingRepository->findById($data['booking_id']);

        if (! $booking) {
            throw new ModelNotFoundException('Booking not found.');
        }

        if ($booking->user_id !== $user->id) {
            throw new ReviewNotAllowedException('This booking does not belong to you.');
        }

        if ($booking->status !== BookingStatus::COMPLETED) {
            throw new ReviewNotAllowedException('You can only review a completed booking.');
        }

        if ($this->reviewRepository->findByBookingId($booking->id)) {
            throw new ReviewNotAllowedException('You have already reviewed this booking.');
        }

        $review = $this->reviewRepository->create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'hotel_id' => $booking->room->roomType->hotel_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ? ProfanityFilter::censor($data['comment']) : null,
        ]);

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'review.created',
            'context' => ['review_id' => $review->id, 'booking_id' => $booking->id],
        ]);

        return $review;
    }

    public function listByHotel(int $hotelId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->reviewRepository->paginateByHotel($hotelId, $perPage);
    }

    /**
     * @throws ReviewNotAllowedException
     */
    public function update(Review $review, array $data, User $actingUser): Review
    {
        if ($review->user_id !== $actingUser->id) {
            throw new ReviewNotAllowedException('You can only update your own review.');
        }

        if (isset($data['comment'])) {
            $data['comment'] = ProfanityFilter::censor($data['comment']);
        }

        $updated = $this->reviewRepository->update($review, $data);

        $this->logRepository->record([
            'user_id' => $actingUser->id,
            'action' => 'review.updated',
            'context' => ['review_id' => $review->id, 'fields' => array_keys($data)],
        ]);

        return $updated;
    }

    /**
     * @throws ReviewNotAllowedException
     */
    public function delete(Review $review, User $actingUser): void
    {
        if ($review->user_id !== $actingUser->id) {
            throw new ReviewNotAllowedException('You can only delete your own review.');
        }

        $this->reviewRepository->delete($review);

        $this->logRepository->record([
            'user_id' => $actingUser->id,
            'action' => 'review.deleted',
            'context' => ['review_id' => $review->id],
        ]);
    }
}

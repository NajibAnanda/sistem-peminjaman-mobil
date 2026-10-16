<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PhotoSource;
use App\Enums\PhotoStage;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BookingPhoto>
 */
final class BookingPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'stage' => PhotoStage::Pickup,
            'source' => PhotoSource::Upload,
            'path' => 'bookings/'.now()->format('Y/m').'/'.Str::uuid().'.jpg',
            'taken_at' => now(),
            'uploaded_by' => User::factory(),
        ];
    }
}

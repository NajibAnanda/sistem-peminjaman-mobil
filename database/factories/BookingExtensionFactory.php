<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingExtension>
 */
final class BookingExtensionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->active(),
            'previous_planned_return_at' => now()->addHours(2),
            'new_planned_return_at' => now()->addHours(5),
            'reason' => 'Rapat dengan klien diperpanjang',
            'extended_by' => User::factory(),
        ];
    }
}

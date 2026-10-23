<?php

namespace Tests;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

/**
 * @property User $user
 * @property Car $car
 * @property Car $carA
 * @property Car $carB
 * @property Employee $employee
 * @property Employee $rina
 * @property Employee $budi
 * @property Booking $booking
 * @property Booking $late
 * @property Booking $scheduled
 * @property User $staff
 */
abstract class TestCase extends BaseTestCase
{
    public ?User $user = null;

    public ?Car $car = null;

    public ?Car $carA = null;

    public ?Car $carB = null;

    public ?Employee $employee = null;

    public ?Employee $rina = null;

    public ?Employee $budi = null;

    public ?Booking $booking = null;

    public ?Booking $late = null;

    public ?Booking $scheduled = null;

    public ?User $staff = null;

    public ?User $manager = null;

    public ?User $admin = null;

    public ?Booking $overdue = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Test tidak boleh bergantung pada hasil `npm run build` yang mungkin
        // belum berisi halaman baru.
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}

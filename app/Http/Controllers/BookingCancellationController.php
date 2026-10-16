<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Booking\CancelBooking;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

final class BookingCancellationController extends Controller
{
    public function store(CancelBookingRequest $request, Booking $booking, CancelBooking $action): RedirectResponse
    {
        $action->handle($booking, $request->reason(), $request->user());

        return to_route('bookings.show', $booking)->with('success', 'Peminjaman berhasil dibatalkan.');
    }
}

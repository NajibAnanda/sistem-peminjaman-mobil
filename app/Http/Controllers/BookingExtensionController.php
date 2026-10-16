<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Booking\ExtendBooking;
use App\Http\Requests\Booking\ExtendBookingRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

final class BookingExtensionController extends Controller
{
    public function store(ExtendBookingRequest $request, Booking $booking, ExtendBooking $action): RedirectResponse
    {
        $action->handle($booking, $request->newPlannedReturn(), $request->reason(), $request->user());

        return to_route('bookings.show', $booking)->with('success', 'Peminjaman berhasil diperpanjang.');
    }
}

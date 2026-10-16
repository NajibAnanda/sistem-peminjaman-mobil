<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Booking\PickUpBooking;
use App\Http\Requests\Booking\HandoverRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

final class BookingPickupController extends Controller
{
    public function store(HandoverRequest $request, Booking $booking, PickUpBooking $action): RedirectResponse
    {
        $action->handle($booking, $request->photo(), $request->photoSource(), $request->user());

        return to_route('bookings.show', $booking)->with('success', 'Serah terima ambil berhasil disimpan.');
    }
}

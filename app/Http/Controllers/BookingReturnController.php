<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Booking\ReturnBooking;
use App\Http\Requests\Booking\HandoverRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

final class BookingReturnController extends Controller
{
    public function store(HandoverRequest $request, Booking $booking, ReturnBooking $action): RedirectResponse
    {
        $action->handle($booking, $request->photo(), $request->photoSource(), $request->returnNotes(), $request->user());

        return to_route('bookings.show', $booking)->with('success', 'Serah terima kembali berhasil disimpan. Peminjaman selesai.');
    }
}

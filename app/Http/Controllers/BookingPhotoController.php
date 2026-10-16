<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PhotoStage;
use App\Models\Booking;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu-satunya jalan membuka foto bukti (disk privat): wajib login dan lolos
 * Policy (AGENTS.md 6.3).
 */
final class BookingPhotoController extends Controller
{
    public function show(Booking $booking, PhotoStage $stage): StreamedResponse
    {
        Gate::authorize('viewPhoto', $booking);

        $photo = $booking->photos()->where('stage', $stage)->firstOrFail();

        return Storage::disk('local')->response($photo->path, "{$booking->code}-{$stage->value}.jpg", [
            // Foto bukti tidak pernah berubah, tetapi tidak boleh disimpan cache bersama.
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}

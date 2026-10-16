<?php

declare(strict_types=1);

namespace App\Actions\Booking\Concerns;

use App\Enums\PhotoSource;
use App\Enums\PhotoStage;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\User;
use App\Support\ImageProcessor;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Penyimpanan foto bukti serah terima (AGENTS.md 6.3): disk privat, path
 * bookings/{Y}/{m}/{uuid}.jpg, timestamp waktu server.
 *
 * @property-read ImageProcessor $images
 */
trait StoresEvidencePhoto
{
    private const EVIDENCE_DISK = 'local';

    /**
     * Gambar diproses di luar transaksi (lambat) lalu $persist dijalankan.
     * Bila $persist gagal, file yang sudah tersimpan dihapus lagi.
     *
     * @template TResult
     *
     * @param  Closure(string): TResult  $persist  menerima path foto
     * @return TResult
     */
    private function withEvidencePhoto(UploadedFile $photo, Closure $persist): mixed
    {
        $path = $this->images->store($photo, self::EVIDENCE_DISK, 'bookings/'.now()->format('Y/m'));

        try {
            return $persist($path);
        } catch (Throwable $exception) {
            Storage::disk(self::EVIDENCE_DISK)->delete($path);

            throw $exception;
        }
    }

    private function recordEvidencePhoto(Booking $booking, PhotoStage $stage, PhotoSource $source, string $path, User $actor): BookingPhoto
    {
        return BookingPhoto::query()->create([
            'booking_id' => $booking->id,
            'stage' => $stage,
            'source' => $source,
            'path' => $path,
            'taken_at' => now(),
            'uploaded_by' => $actor->id,
        ]);
    }
}

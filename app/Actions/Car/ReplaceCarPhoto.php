<?php

declare(strict_types=1);

namespace App\Actions\Car;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\User;
use App\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ReplaceCarPhoto
{
    /** Foto referensi mobil boleh dilihat tanpa Policy, jadi disimpan di disk publik. */
    private const DISK = 'public';

    private const DIRECTORY = 'cars';

    public function __construct(private readonly ImageProcessor $images) {}

    public function handle(Car $car, int $position, UploadedFile $photo, User $actor): CarPhoto
    {
        $newPath = $this->images->store($photo, self::DISK, self::DIRECTORY);

        try {
            $carPhoto = DB::transaction(function () use ($car, $position, $newPath, $actor): CarPhoto {
                $car = Car::query()->lockForUpdate()->findOrFail($car->id);

                $carPhoto = CarPhoto::query()->firstOrNew(['car_id' => $car->id, 'position' => $position]);
                $oldPath = $carPhoto->exists ? $carPhoto->path : null;

                $carPhoto->path = $newPath;
                $carPhoto->save();

                activity(ActivityLogName::Car)
                    ->performedOn($car)
                    ->causedBy($actor)
                    ->event(ActivityEvent::PhotoUpdated->value)
                    ->withProperties(['position' => $position, 'replaced' => $oldPath !== null])
                    ->log("Memperbarui foto {$position} mobil {$car->plate_number}");

                // Foto referensi lama (bukan foto bukti) dihapus hanya setelah
                // data baru benar-benar tersimpan.
                if ($oldPath !== null) {
                    DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($oldPath));
                }

                return $carPhoto;
            });
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($newPath);

            throw $exception;
        }

        return $carPhoto;
    }
}

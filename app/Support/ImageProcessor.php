<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\ImageProcessingException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;

/**
 * Semua gambar unggahan wajib lewat sini (AGENTS.md 6.3): diproses ulang
 * menjadi JPEG baru, sehingga EXIF dan payload apa pun yang disisipkan di
 * file asli ikut hilang. Nama file asli tidak pernah dipakai.
 *
 * Tidak `final` agar dapat di-mock di test (AGENTS.md 10).
 */
class ImageProcessor
{
    private const JPEG_QUALITY = 82;

    /**
     * @return string path relatif di disk, mis. "cars/{uuid}.jpg"
     */
    public function store(UploadedFile $file, string $disk, string $directory): string
    {
        $maxDimension = (int) config('fleet.photos.max_dimension_px');

        try {
            // autoOrientation memutar foto ponsel sesuai EXIF sebelum EXIF dibuang.
            $manager = new ImageManager(Driver::class, autoOrientation: true, strip: true);

            $encoded = $manager->decodePath((string) $file->getRealPath())
                ->scaleDown($maxDimension, $maxDimension)
                ->encode(new JpegEncoder(quality: self::JPEG_QUALITY, strip: true));
        } catch (ImageException) {
            throw ImageProcessingException::unreadable();
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.jpg';

        if (! Storage::disk($disk)->put($path, $encoded->toString())) {
            throw ImageProcessingException::notStored();
        }

        return $path;
    }
}

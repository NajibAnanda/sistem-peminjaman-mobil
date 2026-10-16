<?php

declare(strict_types=1);

namespace App\Http\Requests\Car;

use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class StoreCarPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Car $car */
        $car = $this->route('car');

        return $this->user()?->can('updatePhoto', $car) ?? false;
    }

    /**
     * `mimetypes` memeriksa isi file (bukan ekstensi), sehingga file lain yang
     * diganti namanya menjadi .jpg tetap ditolak.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.config('fleet.photos.max_upload_kb'),
            ],
        ];
    }

    public function photo(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('photo');
    }

    public function position(): int
    {
        return (int) $this->route('position');
    }
}

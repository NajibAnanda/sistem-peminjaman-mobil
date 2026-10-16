<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

trait ValidatesPhotoUpload
{
    /**
     * AGENTS.md 6.3. `mimetypes` memeriksa isi file (bukan ekstensi), sehingga
     * file lain yang diganti namanya menjadi .jpg tetap ditolak.
     *
     * @return list<string>
     */
    protected function photoRules(): array
    {
        return [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:'.config('fleet.photos.max_upload_kb'),
        ];
    }

    public function photo(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('photo');
    }
}

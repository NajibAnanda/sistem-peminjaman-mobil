<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status HTTP yang ditampilkan sebagai halaman error ramah (bukan halaman
 * bawaan Laravel) saat APP_DEBUG=false.
 */
enum ErrorPageStatus: int
{
    case Forbidden = 403;
    case NotFound = 404;
    case TooManyRequests = 429;
    case ServerError = 500;
    case Unavailable = 503;

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): int => $status->value, self::cases());
    }

    public function title(): string
    {
        return match ($this) {
            self::Forbidden => 'Akses ditolak',
            self::NotFound => 'Halaman tidak ditemukan',
            self::TooManyRequests => 'Terlalu banyak permintaan',
            self::ServerError => 'Terjadi kesalahan',
            self::Unavailable => 'Sedang dalam pemeliharaan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Forbidden => 'Anda tidak memiliki izin untuk membuka halaman ini.',
            self::NotFound => 'Halaman atau data yang Anda cari tidak ada atau sudah dipindahkan.',
            self::TooManyRequests => 'Tunggu sebentar, lalu coba lagi.',
            self::ServerError => 'Terjadi kesalahan pada sistem. Coba lagi beberapa saat lagi; bila terus terjadi, hubungi Pengelola.',
            self::Unavailable => 'Aplikasi sedang diperbarui. Coba lagi beberapa menit lagi.',
        };
    }
}

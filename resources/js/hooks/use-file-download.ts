import { useState } from 'react';
import { toast } from 'sonner';

const REVOKE_DELAY_MS = 1000;
const UNPROCESSABLE = 422;
const UNAUTHENTICATED = 401;
const SESSION_EXPIRED =
    'Sesi Anda telah berakhir. Muat ulang halaman lalu coba lagi.';

/** Pesan error validasi pertama dari respons JSON Laravel (422). */
async function validationMessage(response: Response): Promise<string> {
    const body = (await response.json().catch(() => null)) as {
        message?: string;
        errors?: Record<string, string[]>;
    } | null;

    return (
        Object.values(body?.errors ?? {})[0]?.[0] ??
        body?.message ??
        'Permintaan tidak dapat diproses. Periksa kembali pilihan Anda.'
    );
}

export type UseFileDownloadReturn = {
    /** Kunci unduhan yang sedang berjalan, atau null. */
    downloading: string | null;
    download: (key: string, url: string, fallbackName: string) => Promise<void>;
};

/** Nama file dari header Content-Disposition (filename*= didahulukan). */
function fileNameFrom(header: string | null): string | null {
    if (!header) {
        return null;
    }

    const encoded = /filename\*=UTF-8''([^;]+)/i.exec(header);

    if (encoded) {
        return decodeURIComponent(encoded[1]);
    }

    return /filename="?([^";]+)"?/i.exec(header)?.[1] ?? null;
}

/**
 * Mengunduh file lewat fetch agar tombol bisa menampilkan loading sampai file
 * selesai dibuat server (tautan biasa tidak memberi tahu kapan selesai).
 */
export function useFileDownload(): UseFileDownloadReturn {
    const [downloading, setDownloading] = useState<string | null>(null);

    const download = async (key: string, url: string, fallbackName: string) => {
        setDownloading(key);

        try {
            // Accept JSON: penolakan validasi (mis. periode ekspor) kembali
            // sebagai 422 berisi pesan, bukan pengalihan halaman.
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (response.status === UNPROCESSABLE) {
                toast.error(await validationMessage(response));

                return;
            }

            if (response.status === UNAUTHENTICATED) {
                toast.error(SESSION_EXPIRED);

                return;
            }

            // Sesi berakhir: server mengalihkan ke halaman login (HTML),
            // bukan file. Jangan simpan halaman itu sebagai laporan.
            if (
                !response.ok ||
                response.headers.get('Content-Type')?.includes('text/html')
            ) {
                toast.error(
                    response.ok
                        ? SESSION_EXPIRED
                        : 'File gagal dibuat. Coba lagi beberapa saat lagi.',
                );

                return;
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download =
                fileNameFrom(response.headers.get('Content-Disposition')) ??
                fallbackName;
            link.click();
            setTimeout(() => URL.revokeObjectURL(objectUrl), REVOKE_DELAY_MS);
        } catch {
            toast.error('File gagal diunduh. Periksa koneksi lalu coba lagi.');
        } finally {
            setDownloading(null);
        }
    };

    return { downloading, download };
}

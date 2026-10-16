import imageCompression from 'browser-image-compression';

/** Target ukuran unggah (prd.md NFR-01). */
const TARGET_SIZE_MB = 0.3;

/** Sama dengan config('fleet.photos.max_dimension_px'); server tetap memproses ulang. */
const MAX_DIMENSION_PX = 1600;

/**
 * Mengompres foto di browser sebelum diunggah. Web worker dimatikan karena
 * library memuat skrip worker dari CDN, yang diblokir CSP aplikasi.
 */
export async function compressImage(file: File): Promise<File> {
    const compressed = await imageCompression(file, {
        maxSizeMB: TARGET_SIZE_MB,
        maxWidthOrHeight: MAX_DIMENSION_PX,
        useWebWorker: false,
        fileType: 'image/jpeg',
        initialQuality: 0.8,
    });

    return new File([compressed], 'foto.jpg', { type: 'image/jpeg' });
}

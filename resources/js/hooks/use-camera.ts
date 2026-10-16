import { useCallback, useEffect, useRef, useState } from 'react';

export type CameraStatus = 'idle' | 'starting' | 'ready';

export type UseCameraReturn = {
    status: CameraStatus;
    error: string | null;
    /** Callback ref untuk elemen `<video>` pratinjau. */
    videoRef: (element: HTMLVideoElement | null) => void;
    start: () => Promise<void>;
    stop: () => void;
    /** Mengambil satu bingkai dari kamera sebagai JPEG, lalu mematikan kamera. */
    capture: () => Promise<File>;
};

const JPEG_QUALITY = 0.92;

function errorMessage(error: unknown): string {
    const name = error instanceof DOMException ? error.name : '';

    switch (name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'Izin kamera ditolak. Izinkan akses kamera di pengaturan browser, atau gunakan Unggah File.';
        case 'NotFoundError':
        case 'OverconstrainedError':
            return 'Kamera tidak ditemukan di perangkat ini. Gunakan Unggah File.';
        case 'NotReadableError':
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi, atau gunakan Unggah File.';
        default:
            return 'Kamera tidak dapat dibuka. Gunakan Unggah File.';
    }
}

/**
 * Akses webcam lewat getUserMedia (prd.md 11.6). Kamera selalu dimatikan saat
 * foto diambil, dibatalkan, atau komponen dilepas agar lampu kamera padam.
 */
export function useCamera(): UseCameraReturn {
    const stream = useRef<MediaStream | null>(null);
    const video = useRef<HTMLVideoElement | null>(null);
    const [status, setStatus] = useState<CameraStatus>('idle');
    const [error, setError] = useState<string | null>(null);

    const videoRef = useCallback((element: HTMLVideoElement | null) => {
        video.current = element;

        if (element && stream.current) {
            element.srcObject = stream.current;
        }
    }, []);

    const stop = useCallback(() => {
        stream.current?.getTracks().forEach((track) => track.stop());
        stream.current = null;

        if (video.current) {
            video.current.srcObject = null;
        }

        setStatus('idle');
    }, []);

    useEffect(() => stop, [stop]);

    const start = async () => {
        setError(null);

        // getUserMedia hanya ada di secure context (HTTPS atau localhost).
        if (!navigator.mediaDevices?.getUserMedia) {
            setError(
                'Kamera hanya dapat dipakai saat aplikasi dibuka lewat HTTPS atau localhost. Gunakan Unggah File.',
            );

            return;
        }

        setStatus('starting');

        try {
            stream.current = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            });
            setStatus('ready');
        } catch (cause) {
            setStatus('idle');
            setError(errorMessage(cause));
        }
    };

    const capture = async (): Promise<File> => {
        const element = video.current;

        if (!element || element.videoWidth === 0) {
            throw new Error('Kamera belum siap.');
        }

        const canvas = document.createElement('canvas');
        canvas.width = element.videoWidth;
        canvas.height = element.videoHeight;
        canvas.getContext('2d')?.drawImage(element, 0, 0);

        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY),
        );

        stop();

        if (!blob) {
            throw new Error('Foto tidak dapat diambil.');
        }

        return new File([blob], 'kamera.jpg', { type: 'image/jpeg' });
    };

    return { status, error, videoRef, start, stop, capture };
}

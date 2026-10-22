import { Camera, CircleAlert, RotateCcw, Upload, X } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { LoadingButton } from '@/components/loading-button';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useCamera } from '@/hooks/use-camera';
import { compressImage } from '@/lib/compress-image';
import type { PhotoSource } from '@/types';

const ACCEPTED_TYPES = 'image/jpeg,image/png,image/webp';

export type CapturedPhoto = {
    file: File;
    source: PhotoSource;
    previewUrl: string;
};

type Props = {
    value: CapturedPhoto | null;
    onChange: (photo: CapturedPhoto | null) => void;
    maxUploadKb: number;
    /** Pesan error dari server untuk field foto. */
    error?: string;
    disabled?: boolean;
};

/**
 * Pengambilan 1 foto bukti (prd.md 11.6): kamera atau unggah file, pratinjau,
 * ambil ulang. Foto dikompresi di browser sebelum dikirim; server tetap
 * memproses ulang.
 */
export function PhotoCapture({
    value,
    onChange,
    maxUploadKb,
    error,
    disabled = false,
}: Props) {
    const camera = useCamera();
    const input = useRef<HTMLInputElement>(null);
    const [processing, setProcessing] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (value?.previewUrl && value.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(value.previewUrl);
            }
        };
    }, [value?.previewUrl]);

    const accept = async (file: File, source: PhotoSource) => {
        setProcessing(true);
        setLocalError(null);

        try {
            const compressed = await compressImage(file);

            if (compressed.size > maxUploadKb * 1024) {
                setLocalError(`Ukuran foto maksimal ${maxUploadKb / 1024} MB.`);

                return;
            }

            if (value?.previewUrl && value.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(value.previewUrl);
            }

            onChange({
                file: compressed,
                source,
                previewUrl: URL.createObjectURL(compressed),
            });
        } catch {
            setLocalError(
                'Foto tidak dapat diproses. Pilih file gambar JPG, PNG, atau WebP.',
            );
        } finally {
            setProcessing(false);
        }
    };

    const upload = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (file) {
            void accept(file, 'upload');
        }
    };

    const takePhoto = async () => {
        try {
            await accept(await camera.capture(), 'camera');
        } catch {
            setLocalError('Foto tidak dapat diambil. Coba lagi.');
        }
    };

    const retake = () => {
        if (value?.previewUrl && value.previewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(value.previewUrl);
        }

        setLocalError(null);
        onChange(null);
    };

    const busy = disabled || processing;

    return (
        <div className="grid gap-3">
            {value ? (
                <div className="grid gap-2">
                    <img
                        src={value.previewUrl}
                        alt="Pratinjau foto kondisi mobil"
                        className="aspect-video w-full rounded-lg border border-border bg-muted object-contain"
                    />
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-sm text-muted-foreground">
                            {value.source === 'camera'
                                ? 'Diambil dengan kamera'
                                : 'Diunggah dari file'}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={retake}
                            disabled={busy}
                        >
                            <RotateCcw aria-hidden="true" />
                            Ambil ulang
                        </Button>
                    </div>
                </div>
            ) : camera.status === 'idle' ? (
                <div className="grid gap-3 sm:grid-cols-2">
                    <Button
                        type="button"
                        variant="outline"
                        className="h-24 flex-col gap-2"
                        onClick={() => void camera.start()}
                        disabled={busy}
                        data-test="photo-capture-camera"
                    >
                        <Camera className="size-6" aria-hidden="true" />
                        Ambil dengan Kamera
                    </Button>
                    <LoadingButton
                        type="button"
                        variant="outline"
                        className="h-24 flex-col gap-2"
                        onClick={() => input.current?.click()}
                        loading={processing}
                        disabled={disabled}
                        data-test="photo-capture-upload"
                    >
                        <Upload className="size-6" aria-hidden="true" />
                        Unggah File
                    </LoadingButton>
                </div>
            ) : (
                <div className="grid gap-2">
                    <div className="flex aspect-video w-full items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                        {camera.status === 'ready' ? (
                            <video
                                ref={camera.videoRef}
                                autoPlay
                                playsInline
                                muted
                                className="size-full object-contain"
                                aria-label="Pratinjau kamera"
                            />
                        ) : (
                            <span className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Spinner aria-hidden="true" />
                                Membuka kamera...
                            </span>
                        )}
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={camera.stop}
                            disabled={processing}
                        >
                            <X aria-hidden="true" />
                            Batal
                        </Button>
                        <LoadingButton
                            type="button"
                            size="sm"
                            onClick={() => void takePhoto()}
                            loading={processing}
                            disabled={camera.status !== 'ready'}
                        >
                            <Camera aria-hidden="true" />
                            Ambil Foto
                        </LoadingButton>
                    </div>
                </div>
            )}

            {camera.error && !value && (
                <Alert>
                    <CircleAlert aria-hidden="true" />
                    <AlertDescription>{camera.error}</AlertDescription>
                </Alert>
            )}

            <input
                ref={input}
                type="file"
                accept={ACCEPTED_TYPES}
                className="hidden"
                onChange={upload}
                data-test="photo-capture-input"
                aria-label="Unggah foto bukti"
            />
            <InputError message={localError ?? error} />
        </div>
    );
}

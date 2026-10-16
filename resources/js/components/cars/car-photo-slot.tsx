import { router } from '@inertiajs/react';
import { Camera, Upload } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { PHOTO_SLOT_LABELS } from '@/components/cars/car-gallery-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { compressImage } from '@/lib/compress-image';
import { store } from '@/routes/cars/photos';
import type { CarPhotoSlot as Slot } from '@/types';

const ACCEPTED_TYPES = 'image/jpeg,image/png,image/webp';

type Props = {
    carId: number;
    plateNumber: string;
    slot: Slot;
    maxUploadKb: number;
    onOpen: () => void;
};

export function CarPhotoSlot({
    carId,
    plateNumber,
    slot,
    maxUploadKb,
    onOpen,
}: Props) {
    const input = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | undefined>();
    const label = PHOTO_SLOT_LABELS[slot.position];

    const upload = async (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        setUploading(true);
        setError(undefined);

        let photo: File;

        try {
            photo = await compressImage(file);
        } catch {
            setUploading(false);
            toast.error(
                'Foto tidak dapat diproses. Pilih file gambar JPG, PNG, atau WebP.',
            );

            return;
        }

        if (photo.size > maxUploadKb * 1024) {
            setUploading(false);
            setError(`Ukuran foto maksimal ${maxUploadKb / 1024} MB.`);

            return;
        }

        router.post(
            store({ car: carId, position: slot.position }),
            { photo },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errors) => setError(errors.photo),
                onFinish: () => setUploading(false),
            },
        );
    };

    return (
        <div className="grid gap-2">
            <p className="text-sm font-medium">{label}</p>

            {slot.url ? (
                <button
                    type="button"
                    onClick={onOpen}
                    className="overflow-hidden rounded-lg border border-border focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label={`Lihat foto ${label}`}
                >
                    <img
                        src={slot.url}
                        alt={`${plateNumber}, ${label}`}
                        className="aspect-video w-full bg-muted object-cover"
                    />
                </button>
            ) : (
                // Slot kosong langsung menjadi area unggah agar jelas apa yang
                // perlu dilakukan setelah mobil baru disimpan.
                <button
                    type="button"
                    onClick={() => input.current?.click()}
                    disabled={uploading}
                    className="flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border bg-muted/50 text-muted-foreground transition-colors hover:border-primary hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:pointer-events-none"
                    aria-label={`Unggah foto ${label}`}
                >
                    {uploading ? (
                        <Spinner />
                    ) : (
                        <Upload className="size-6" aria-hidden="true" />
                    )}
                    <span className="text-sm font-medium">
                        {uploading ? 'Mengunggah...' : 'Klik untuk unggah foto'}
                    </span>
                    <span className="text-xs">JPG, PNG, atau WebP</span>
                </button>
            )}

            <input
                ref={input}
                type="file"
                accept={ACCEPTED_TYPES}
                className="hidden"
                onChange={upload}
                data-test={`car-photo-input-${slot.position}`}
            />
            <Button
                variant={slot.url ? 'outline' : 'default'}
                size="sm"
                onClick={() => input.current?.click()}
                disabled={uploading}
            >
                {uploading ? (
                    <Spinner />
                ) : slot.url ? (
                    <Camera aria-hidden="true" />
                ) : (
                    <Upload aria-hidden="true" />
                )}
                {uploading
                    ? 'Mengunggah...'
                    : slot.url
                      ? 'Ganti foto'
                      : 'Unggah foto'}
            </Button>
            <InputError message={error} />
        </div>
    );
}

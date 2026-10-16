import { ChevronLeft, ChevronRight } from 'lucide-react';
import { CarPhotoPlaceholder } from '@/components/cars/car-photo-placeholder';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CarPhotoSlot } from '@/types';

/** Keterangan tiap slot foto referensi (prd.md FR-08). */
export const PHOTO_SLOT_LABELS: Record<number, string> = {
    1: 'Tampak depan/samping',
    2: 'Tampak belakang/interior',
};

type Props = {
    title: string;
    photos: CarPhotoSlot[];
    /** Indeks foto yang sedang ditampilkan; null = dialog tertutup. */
    index: number | null;
    onIndexChange: (index: number | null) => void;
};

/**
 * Galeri foto referensi: Dialog dengan tombol sebelumnya/selanjutnya,
 * tanpa komponen carousel (AGENTS.md 8.3).
 */
export function CarGalleryDialog({
    title,
    photos,
    index,
    onIndexChange,
}: Props) {
    if (index === null) {
        return null;
    }

    const photo = photos[index];
    const last = photos.length - 1;

    return (
        <Dialog open onOpenChange={(open) => !open && onIndexChange(null)}>
            <DialogContent
                className="sm:max-w-3xl"
                onKeyDown={(event) => {
                    if (event.key === 'ArrowLeft' && index > 0) {
                        onIndexChange(index - 1);
                    }

                    if (event.key === 'ArrowRight' && index < last) {
                        onIndexChange(index + 1);
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        Foto {index + 1} dari {photos.length} —{' '}
                        {PHOTO_SLOT_LABELS[photo.position]}
                    </DialogDescription>
                </DialogHeader>

                <div className="overflow-hidden rounded-lg">
                    {photo.url ? (
                        <img
                            src={photo.url}
                            alt={`${title}, ${PHOTO_SLOT_LABELS[photo.position]}`}
                            className="max-h-[70vh] w-full bg-muted object-contain"
                        />
                    ) : (
                        <CarPhotoPlaceholder
                            label="Foto belum diunggah"
                            className="aspect-video w-full"
                        />
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <Button
                        variant="outline"
                        onClick={() => onIndexChange(index - 1)}
                        disabled={index === 0}
                    >
                        <ChevronLeft aria-hidden="true" />
                        Sebelumnya
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => onIndexChange(index + 1)}
                        disabled={index === last}
                    >
                        Selanjutnya
                        <ChevronRight aria-hidden="true" />
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}

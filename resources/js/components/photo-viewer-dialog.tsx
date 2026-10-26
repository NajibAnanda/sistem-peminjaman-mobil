import { useState } from 'react';
import { LoadingImage } from '@/components/loading-image';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type PhotoItem = { url: string; alt: string; title: string; caption: string };

type Props = {
    /** null = dialog tertutup. */
    photo: PhotoItem | null;
    onClose: () => void;
};

/**
 * Tampilan foto ukuran penuh. Lebarnya sama dengan galeri foto mobil (kelompok
 * dialog foto, AGENTS.md 8.3); foto ditampilkan utuh (object-contain).
 */
export function PhotoViewerDialog({ photo, onClose }: Props) {
    const [current, setCurrent] = useState(photo);

    if (photo && photo !== current) {
        setCurrent(photo);
    }

    const display = photo ?? current;

    return (
        <Dialog
            open={photo !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="sm:max-w-3xl">
                {display && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{display.title}</DialogTitle>
                            <DialogDescription>
                                {display.caption}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="relative h-[min(70svh,40rem)] w-full overflow-hidden rounded-lg bg-muted">
                            <LoadingImage
                                src={display.url}
                                alt={display.alt}
                                className="size-full object-contain"
                            />
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

import { LoadingImage } from '@/components/loading-image';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    /** null = dialog tertutup. */
    photo: { url: string; alt: string; title: string; caption: string } | null;
    onClose: () => void;
};

/**
 * Tampilan foto ukuran penuh. Lebarnya sama dengan galeri foto mobil (kelompok
 * dialog foto, AGENTS.md 8.3); foto ditampilkan utuh (object-contain).
 */
export function PhotoViewerDialog({ photo, onClose }: Props) {
    return (
        <Dialog
            open={photo !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="sm:max-w-3xl">
                {photo && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{photo.title}</DialogTitle>
                            <DialogDescription>
                                {photo.caption}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="relative h-[min(70svh,40rem)] w-full overflow-hidden rounded-lg bg-muted">
                            <LoadingImage
                                src={photo.url}
                                alt={photo.alt}
                                className="size-full object-contain"
                            />
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

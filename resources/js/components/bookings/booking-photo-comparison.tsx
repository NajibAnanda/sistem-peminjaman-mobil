import { ImageOff } from 'lucide-react';
import { useDateFormat } from '@/hooks/use-date-format';
import type { BookingPhoto } from '@/types';

type Props = {
    pickup: BookingPhoto | null;
    returned: BookingPhoto | null;
};

/** Foto sebelum & sesudah berdampingan (prd.md FR-19). */
export function BookingPhotoComparison({ pickup, returned }: Props) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <PhotoPanel
                title="Saat diambil"
                photo={pickup}
                emptyText="Foto diambil saat serah terima ambil."
            />
            <PhotoPanel
                title="Saat dikembalikan"
                photo={returned}
                emptyText="Foto diambil saat serah terima kembali."
            />
        </div>
    );
}

function PhotoPanel({
    title,
    photo,
    emptyText,
}: {
    title: string;
    photo: BookingPhoto | null;
    emptyText: string;
}) {
    const { formatDateTime } = useDateFormat();

    return (
        <figure className="grid min-w-0 gap-2">
            <figcaption className="text-sm font-medium">{title}</figcaption>
            {photo ? (
                <>
                    <a
                        href={photo.url}
                        target="_blank"
                        rel="noopener"
                        className="overflow-hidden rounded-lg border border-border focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        aria-label={`Buka foto ${title.toLowerCase()} ukuran penuh`}
                    >
                        <img
                            src={photo.url}
                            alt={`Kondisi mobil ${title.toLowerCase()}`}
                            className="aspect-video w-full bg-muted object-cover"
                        />
                    </a>
                    <p className="text-xs text-muted-foreground">
                        {formatDateTime(photo.taken_at)} · {photo.source_label}{' '}
                        · oleh {photo.uploaded_by_name}
                    </p>
                </>
            ) : (
                <div className="flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border bg-muted/50 p-4 text-center text-muted-foreground">
                    <ImageOff className="size-6" aria-hidden="true" />
                    <span className="text-sm">Belum ada foto</span>
                    <span className="text-xs">{emptyText}</span>
                </div>
            )}
        </figure>
    );
}

import { ImageOff } from 'lucide-react';
import { useState } from 'react';
import { LoadingImage } from '@/components/loading-image';
import { PhotoViewerDialog } from '@/components/photo-viewer-dialog';
import { useDateFormat } from '@/hooks/use-date-format';
import type { BookingPhoto } from '@/types';

type Props = {
    pickup: BookingPhoto | null;
    returned: BookingPhoto | null;
};

type Viewing = { url: string; alt: string; title: string; caption: string };

/**
 * Foto sebelum & sesudah berdampingan (prd.md FR-19): dua card selebar sama,
 * area foto 4:3 dengan object-contain sehingga foto portrait/landscape tetap
 * utuh; klik foto membuka ukuran penuh.
 */
export function BookingPhotoComparison({ pickup, returned }: Props) {
    const [viewing, setViewing] = useState<Viewing | null>(null);

    return (
        <>
            <div className="@container">
                <div className="grid gap-4 @lg:grid-cols-2">
                    <PhotoPanel
                        title="Saat diambil"
                        photo={pickup}
                        emptyTitle="Belum ada foto pengambilan"
                        emptyText="Foto diambil saat serah terima ambil."
                        onOpen={setViewing}
                    />
                    <PhotoPanel
                        title="Saat dikembalikan"
                        photo={returned}
                        emptyTitle="Belum ada foto pengembalian"
                        emptyText="Foto diambil saat serah terima kembali."
                        onOpen={setViewing}
                    />
                </div>
            </div>

            <PhotoViewerDialog
                photo={viewing}
                onClose={() => setViewing(null)}
            />
        </>
    );
}

function PhotoPanel({
    title,
    photo,
    emptyTitle,
    emptyText,
    onOpen,
}: {
    title: string;
    photo: BookingPhoto | null;
    emptyTitle: string;
    emptyText: string;
    onOpen: (viewing: Viewing) => void;
}) {
    const { formatDateTime } = useDateFormat();
    const alt = `Kondisi mobil ${title.toLowerCase()}`;
    const caption = photo
        ? `${formatDateTime(photo.taken_at)} · ${photo.source_label} · oleh ${photo.uploaded_by_name}`
        : null;

    return (
        <figure className="flex min-w-0 flex-col overflow-hidden rounded-lg border border-border">
            <figcaption className="border-b border-border px-3 py-2 text-sm font-medium">
                {title}
            </figcaption>

            <div className="relative aspect-4/3 bg-muted">
                {photo ? (
                    <button
                        type="button"
                        onClick={() =>
                            onOpen({
                                url: photo.url,
                                alt,
                                title: `Foto ${title.toLowerCase()}`,
                                caption: caption ?? '',
                            })
                        }
                        className="absolute inset-0 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none focus-visible:ring-inset"
                        aria-label={`Lihat foto ${title.toLowerCase()} ukuran penuh`}
                    >
                        <LoadingImage
                            src={photo.url}
                            alt={alt}
                            className="size-full object-contain"
                        />
                    </button>
                ) : (
                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center text-muted-foreground">
                        <ImageOff className="size-6" aria-hidden="true" />
                        <span className="text-sm font-medium">
                            {emptyTitle}
                        </span>
                        <span className="text-xs">{emptyText}</span>
                    </div>
                )}
            </div>

            {/* Selalu satu baris keterangan agar tinggi kedua card sama. */}
            <p className="min-h-9 border-t border-border px-3 py-2 text-xs text-muted-foreground">
                {caption ?? 'Waktu dan petugas tampil setelah foto diambil.'}
            </p>
        </figure>
    );
}

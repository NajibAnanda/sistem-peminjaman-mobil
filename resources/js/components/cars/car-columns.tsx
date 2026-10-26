import { CircleCheck } from 'lucide-react';
import { CarPhotoPlaceholder } from '@/components/cars/car-photo-placeholder';
import { PhotosIncompleteBadge } from '@/components/cars/photos-incomplete-badge';
import { TruncatedText } from '@/components/data-table/truncated-text';
import type { DataTableColumn } from '@/components/data-table/types';
import { LoadingImage } from '@/components/loading-image';
import { StatusBadge } from '@/components/status-badge';
import type { Car } from '@/types';

export function useCarColumns(): DataTableColumn<Car>[] {
    return [
        {
            id: 'thumbnail',
            header: 'Foto',
            skeleton: 'thumbnail',
            cell: (car) => {
                const thumbnail = car.photos.find((photo) => photo.url)?.url;

                return (
                    <div className="relative h-10 w-14 overflow-hidden rounded-md border border-border">
                        {thumbnail ? (
                            <LoadingImage
                                src={thumbnail}
                                alt={`Foto ${car.brand} ${car.model} (${car.plate_number})`}
                                className="size-full object-cover"
                                compact
                            />
                        ) : (
                            <CarPhotoPlaceholder className="size-full" />
                        )}
                    </div>
                );
            },
        },
        {
            id: 'model',
            header: 'Merk & tipe',
            cell: (car) => (
                <TruncatedText
                    text={`${car.brand} ${car.model}`}
                    className="max-w-56 font-medium"
                />
            ),
        },
        {
            id: 'plate_number',
            header: 'Plat',
            cell: (car) => (
                <span className="tabular-nums">{car.plate_number}</span>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            skeleton: 'badge',
            cell: (car) => <StatusBadge status={car.display_status} />,
        },
        {
            id: 'photos',
            header: 'Foto referensi',
            skeleton: 'badge',
            cell: (car) =>
                car.photos_complete ? (
                    <span className="inline-flex items-center gap-1 text-muted-foreground">
                        <CircleCheck className="size-4" aria-hidden="true" />
                        Lengkap
                    </span>
                ) : (
                    <PhotosIncompleteBadge />
                ),
        },
    ];
}

import { CircleCheck } from 'lucide-react';
import { CarPhotoPlaceholder } from '@/components/cars/car-photo-placeholder';
import { PhotosIncompleteBadge } from '@/components/cars/photos-incomplete-badge';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import type { Car } from '@/types';

export function useCarColumns(): DataTableColumn<Car>[] {
    return [
        {
            id: 'car',
            header: 'Mobil',
            cell: (car) => {
                const thumbnail = car.photos.find((photo) => photo.url)?.url;

                return (
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-14 shrink-0 overflow-hidden rounded-md border border-border">
                            {thumbnail ? (
                                <img
                                    src={thumbnail}
                                    alt=""
                                    className="size-full bg-muted object-cover"
                                />
                            ) : (
                                <CarPhotoPlaceholder className="size-full" />
                            )}
                        </div>
                        <div>
                            <div className="font-medium">
                                {car.plate_number}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                {car.brand} {car.model}
                            </div>
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'status',
            header: 'Status',
            cell: (car) => <StatusBadge status={car.display_status} />,
        },
        {
            id: 'photos',
            header: 'Foto referensi',
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

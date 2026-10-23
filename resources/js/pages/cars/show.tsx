import { Head } from '@inertiajs/react';
import { CalendarClock, Pencil, Power, PowerOff, Wrench } from 'lucide-react';
import { useState } from 'react';
import { carActivationConfirmation } from '@/components/cars/car-confirmations';
import { CarFormDialog } from '@/components/cars/car-form-dialog';
import { CarGalleryDialog } from '@/components/cars/car-gallery-dialog';
import { CarPhotoSlot } from '@/components/cars/car-photo-slot';
import { PhotosIncompleteBadge } from '@/components/cars/photos-incomplete-badge';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ConfirmActionConfig } from '@/hooks/use-confirm-action';
import { useConfirmAction } from '@/hooks/use-confirm-action';
import { useDateFormat } from '@/hooks/use-date-format';
import { index, operationalStatus, show } from '@/routes/cars';
import type { BookingSummary, Car, CarShowProps } from '@/types';

export default function CarShow({
    car,
    activeBooking,
    scheduledBookings,
    maxUploadKb,
}: CarShowProps) {
    const { formatDateTime } = useDateFormat();
    const confirmation = useConfirmAction();
    const [formOpen, setFormOpen] = useState(false);
    const [formKey, setFormKey] = useState(0);
    const [galleryIndex, setGalleryIndex] = useState<number | null>(null);

    const openForm = () => {
        setFormKey((key) => key + 1);
        setFormOpen(true);
    };

    return (
        <>
            <Head title={`${car.brand} ${car.model} (${car.plate_number})`} />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title={`${car.brand} ${car.model}`}
                    description={car.plate_number}
                    actions={
                        <>
                            <Button variant="outline" onClick={openForm}>
                                <Pencil aria-hidden="true" />
                                Edit
                            </Button>
                            {car.is_active && (
                                <Button
                                    variant="outline"
                                    disabled={car.display_status === 'borrowed'}
                                    title={
                                        car.display_status === 'borrowed'
                                            ? 'Mobil sedang dipinjam'
                                            : undefined
                                    }
                                    onClick={() =>
                                        confirmation.request(
                                            statusConfirmation(
                                                car,
                                                scheduledBookings,
                                                formatDateTime,
                                            ),
                                        )
                                    }
                                >
                                    <Wrench aria-hidden="true" />
                                    {car.operational_status === 'maintenance'
                                        ? 'Selesai Maintenance'
                                        : 'Set Maintenance'}
                                </Button>
                            )}
                            <Button
                                variant={
                                    car.is_active ? 'destructive' : 'outline'
                                }
                                onClick={() =>
                                    confirmation.request(
                                        carActivationConfirmation(car),
                                    )
                                }
                            >
                                {car.is_active ? (
                                    <PowerOff aria-hidden="true" />
                                ) : (
                                    <Power aria-hidden="true" />
                                )}
                                {car.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                Foto Referensi
                                {!car.photos_complete && (
                                    <PhotosIncompleteBadge />
                                )}
                            </CardTitle>
                            <CardDescription>
                                Foto tampak depan dan belakang mobil sebagai
                                acuan kondisi. Klik foto untuk memperbesar.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            {car.photos.map((slot, slotIndex) => (
                                <CarPhotoSlot
                                    key={slot.position}
                                    carId={car.id}
                                    plateNumber={car.plate_number}
                                    slot={slot}
                                    maxUploadKb={maxUploadKb}
                                    onOpen={() => setGalleryIndex(slotIndex)}
                                />
                            ))}
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Status</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 text-sm">
                                <StatusBadge status={car.display_status} />
                                {activeBooking ? (
                                    <div className="grid gap-1 rounded-lg bg-muted p-3">
                                        <span className="font-medium">
                                            Sedang dipinjam (
                                            {activeBooking.code})
                                        </span>
                                        <span>
                                            {activeBooking.employee_name}
                                        </span>
                                        <span className="text-muted-foreground">
                                            Rencana kembali{' '}
                                            {formatDateTime(
                                                activeBooking.planned_return_at,
                                            )}
                                        </span>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">
                                        Tidak sedang dipinjam.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Jadwal Mendatang</CardTitle>
                                <CardDescription>
                                    Daftar peminjaman terjadwal yang akan
                                    memakai mobil ini dalam waktu dekat.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {scheduledBookings.length > 0 ? (
                                    <ul className="grid gap-3 text-sm">
                                        {scheduledBookings.map((booking) => (
                                            <li
                                                key={booking.id}
                                                className="grid gap-0.5"
                                            >
                                                <span className="font-medium">
                                                    {booking.code} ·{' '}
                                                    {booking.employee_name}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {formatDateTime(
                                                        booking.start_at,
                                                    )}{' '}
                                                    –{' '}
                                                    {formatDateTime(
                                                        booking.planned_return_at,
                                                    )}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <EmptyState
                                        icon={CalendarClock}
                                        title="Tidak ada jadwal"
                                        description="Belum ada peminjaman yang dijadwalkan untuk mobil ini."
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <CarFormDialog
                key={formKey}
                open={formOpen}
                onOpenChange={setFormOpen}
                car={car}
            />

            <CarGalleryDialog
                title={car.plate_number}
                photos={car.photos}
                index={galleryIndex}
                onIndexChange={setGalleryIndex}
            />

            <ConfirmDialog {...confirmation.dialogProps} />
        </>
    );
}

function statusConfirmation(
    car: Car,
    scheduledBookings: BookingSummary[],
    formatDateTime: (value: string) => string,
): ConfirmActionConfig {
    if (car.operational_status === 'maintenance') {
        return {
            title: 'Selesai maintenance?',
            description: `${car.plate_number} kembali Operasional dan dapat dijadwalkan lagi.`,
            confirmLabel: 'Kembalikan ke Operasional',
            route: operationalStatus(car.id),
            data: { operational_status: 'operational' },
        };
    }

    return {
        title: 'Set mobil ke Maintenance?',
        description: (
            <>
                {car.plate_number} tidak dapat dijadwalkan atau diserahterimakan
                selama Maintenance.
                {scheduledBookings.length > 0 && (
                    <span className="mt-2 block text-warning">
                        Jadwal berikut perlu dipindahkan ke mobil lain:{' '}
                        {scheduledBookings
                            .map(
                                (booking) =>
                                    `${booking.code} (${booking.employee_name}, ${formatDateTime(booking.start_at)})`,
                            )
                            .join(', ')}
                        .
                    </span>
                )}
            </>
        ),
        confirmLabel: 'Set Maintenance',
        destructive: true,
        route: operationalStatus(car.id),
        data: { operational_status: 'maintenance' },
    };
}

// Breadcrumb dihitung dari props saat render (bukan setLayoutProps di dalam
// komponen) agar hasil SSR sama dengan klien dan tidak terjadi hydration mismatch.
CarShow.layout = ({ car }: CarShowProps) => ({
    breadcrumbs: [
        { title: 'Data Mobil', href: index() },
        {
            title: `${car.brand} ${car.model} (${car.plate_number})`,
            href: show(car.id),
        },
    ],
});

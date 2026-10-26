import { Head, Link } from '@inertiajs/react';
import { Ban, CalendarPlus, KeyRound, Pencil, Undo2 } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    AwaitingReturnAlert,
    CarInUseAlert,
} from '@/components/bookings/awaiting-return-notice';
import { BookingActionDialogs } from '@/components/bookings/booking-action-dialogs';
import { BookingPhotoComparison } from '@/components/bookings/booking-photo-comparison';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useBookingDialogs } from '@/hooks/use-booking-dialogs';
import { useDateFormat } from '@/hooks/use-date-format';
import { edit, index, show } from '@/routes/bookings';
import { show as showCar } from '@/routes/cars';
import { index as historyIndex } from '@/routes/history';
import { index as scheduleIndex } from '@/routes/schedule';
import type { BookingShowProps } from '@/types';

export default function BookingShow({
    booking,
    maxUploadKb,
    from,
}: BookingShowProps) {
    const { formatDateTime } = useDateFormat();
    const dialogs = useBookingDialogs();
    const { actions } = booking;

    return (
        <>
            <Head title={booking.code} />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight tabular-nums">
                                {booking.code}
                            </h1>
                            <StatusBadge status={booking.display_status} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {booking.purpose}
                        </p>
                    </div>
                    <div className="flex shrink-0 flex-wrap gap-2">
                        {actions.edit && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={edit(
                                        booking.id,
                                        from ? { query: { from } } : undefined,
                                    )}
                                >
                                    <Pencil aria-hidden="true" />
                                    Edit Jadwal
                                </Link>
                            </Button>
                        )}
                        {actions.cancel && (
                            <Button
                                variant="destructive"
                                onClick={() => dialogs.open('cancel', booking)}
                            >
                                <Ban aria-hidden="true" />
                                Batalkan
                            </Button>
                        )}
                        {actions.extend && (
                            <Button
                                variant="outline"
                                onClick={() => dialogs.open('extend', booking)}
                            >
                                <CalendarPlus aria-hidden="true" />
                                Perpanjang
                            </Button>
                        )}
                        {actions.pickup && (
                            <Button
                                onClick={() => dialogs.open('pickup', booking)}
                                disabled={Boolean(
                                    booking.awaiting_return_code ||
                                    booking.active_car_booking_code,
                                )}
                                title={
                                    booking.awaiting_return_code
                                        ? `Mobil belum kembali dari ${booking.awaiting_return_code}`
                                        : booking.active_car_booking_code
                                          ? `Mobil sedang digunakan oleh ${booking.active_car_booking_code}`
                                          : undefined
                                }
                                data-test="pickup-button"
                            >
                                <KeyRound aria-hidden="true" />
                                Serah Terima Ambil
                            </Button>
                        )}
                        {actions.return && (
                            <Button
                                onClick={() => dialogs.open('return', booking)}
                                data-test="return-button"
                            >
                                <Undo2 aria-hidden="true" />
                                Serah Terima Kembali
                            </Button>
                        )}
                    </div>
                </div>

                {booking.awaiting_return_code ? (
                    <AwaitingReturnAlert
                        code={booking.awaiting_return_code}
                        id={booking.awaiting_return_id}
                    />
                ) : booking.active_car_booking_code ? (
                    <CarInUseAlert
                        code={booking.active_car_booking_code}
                        id={booking.active_car_booking_id}
                    />
                ) : null}

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="flex min-w-0 flex-col gap-4 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Informasi Peminjaman</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                                    <Field label="Karyawan">
                                        <span className="font-medium">
                                            {booking.employee.name}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {booking.employee.division}
                                        </span>
                                        <span className="text-muted-foreground tabular-nums">
                                            {booking.employee.phone}
                                        </span>
                                    </Field>
                                    <Field label="Mobil">
                                        <Link
                                            href={showCar(booking.car.id)}
                                            className="rounded-sm font-medium underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            {booking.car.brand}{' '}
                                            {booking.car.model}
                                        </Link>
                                        <span className="text-muted-foreground tabular-nums">
                                            {booking.car.plate_number}
                                        </span>
                                    </Field>
                                    <Field label="Waktu mulai (rencana)">
                                        {formatDateTime(booking.start_at)}
                                    </Field>
                                    <Field label="Rencana kembali">
                                        {formatDateTime(
                                            booking.planned_return_at,
                                        )}
                                    </Field>
                                    <Field label="Diambil">
                                        {booking.picked_up_at ? (
                                            <>
                                                {formatDateTime(
                                                    booking.picked_up_at,
                                                )}
                                                {booking.picked_up_by_name && (
                                                    <Muted>
                                                        oleh{' '}
                                                        {
                                                            booking.picked_up_by_name
                                                        }
                                                    </Muted>
                                                )}
                                            </>
                                        ) : (
                                            <Muted>Belum</Muted>
                                        )}
                                    </Field>
                                    <Field label="Dikembalikan">
                                        {booking.returned_at ? (
                                            <>
                                                {formatDateTime(
                                                    booking.returned_at,
                                                )}
                                                {booking.returned_by_name && (
                                                    <Muted>
                                                        oleh{' '}
                                                        {
                                                            booking.returned_by_name
                                                        }
                                                    </Muted>
                                                )}
                                            </>
                                        ) : (
                                            <Muted>Belum</Muted>
                                        )}
                                    </Field>
                                    {booking.return_notes && (
                                        <Field label="Catatan kondisi" wide>
                                            <span className="whitespace-pre-line">
                                                {booking.return_notes}
                                            </span>
                                        </Field>
                                    )}
                                    {booking.cancellation_reason && (
                                        <Field label="Alasan pembatalan" wide>
                                            <span className="whitespace-pre-line">
                                                {booking.cancellation_reason}
                                            </span>
                                            {booking.cancelled_at && (
                                                <Muted>
                                                    {formatDateTime(
                                                        booking.cancelled_at,
                                                    )}
                                                    {booking.cancelled_by_name &&
                                                        ` oleh ${booking.cancelled_by_name}`}
                                                </Muted>
                                            )}
                                        </Field>
                                    )}
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Foto Kondisi Mobil</CardTitle>
                                <CardDescription>
                                    Foto kondisi mobil saat diambil dan
                                    dikembalikan, dengan waktu yang dicatat
                                    otomatis oleh sistem.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <BookingPhotoComparison
                                    pickup={booking.photos.pickup}
                                    returned={booking.photos.return}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex min-w-0 flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Riwayat Perpanjangan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {booking.extensions.length > 0 ? (
                                    <ol className="grid gap-4 text-sm">
                                        {booking.extensions.map((extension) => (
                                            <li
                                                key={extension.id}
                                                className="grid gap-0.5 border-l-2 border-border pl-3"
                                            >
                                                <span className="font-medium tabular-nums">
                                                    {formatDateTime(
                                                        extension.previous_planned_return_at,
                                                    )}{' '}
                                                    →{' '}
                                                    {formatDateTime(
                                                        extension.new_planned_return_at,
                                                    )}
                                                </span>
                                                <span className="whitespace-pre-line">
                                                    {extension.reason}
                                                </span>
                                                <Muted>
                                                    {formatDateTime(
                                                        extension.created_at,
                                                    )}{' '}
                                                    oleh{' '}
                                                    {extension.extended_by_name}
                                                </Muted>
                                            </li>
                                        ))}
                                    </ol>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Belum pernah diperpanjang.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Pencatatan</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm">
                                <p>
                                    Dijadwalkan oleh {booking.created_by_name}
                                </p>
                                <Muted>
                                    {formatDateTime(booking.created_at)}
                                </Muted>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <BookingActionDialogs dialogs={dialogs} maxUploadKb={maxUploadKb} />
        </>
    );
}

function Field({
    label,
    wide = false,
    children,
}: {
    label: string;
    wide?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={
                wide
                    ? 'grid content-start gap-1 sm:col-span-2'
                    : 'grid content-start gap-1'
            }
        >
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="grid content-start gap-0.5">{children}</dd>
        </div>
    );
}

function Muted({ children }: { children: ReactNode }) {
    return <span className="block text-muted-foreground">{children}</span>;
}

// Breadcrumb dihitung dari props saat render (bukan setLayoutProps di dalam
// komponen) agar hasil SSR sama dengan klien dan tidak terjadi hydration mismatch.
BookingShow.layout = ({ booking, from }: BookingShowProps) => {
    let parentTitle = 'Peminjaman';
    let parentHref = index();

    if (from === 'history') {
        parentTitle = 'Riwayat';
        parentHref = historyIndex();
    } else if (from === 'schedule') {
        parentTitle = 'Jadwal Armada';
        parentHref = scheduleIndex();
    }

    return {
        breadcrumbs: [
            { title: parentTitle, href: parentHref },
            {
                title: booking.code,
                href: show(booking.id, from ? { query: { from } } : undefined),
            },
        ],
    };
};

import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import {
    AlarmClockOff,
    CalendarClock,
    Clock,
    KeyRound,
    Phone,
    Plus,
    Undo2,
} from 'lucide-react';
import { BookingListCard } from '@/components/dashboard/booking-list-card';
import { FleetSummary } from '@/components/dashboard/fleet-summary';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { useDateFormat } from '@/hooks/use-date-format';
import { formatDuration } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { create, index as bookings } from '@/routes/bookings';
import type { Booking, DashboardProps } from '@/types';

const SECOND_MS = 1000;

export default function Dashboard({
    now,
    alertListLimit,
    fleet,
    pickupsToday,
    returnsToday,
    overdue,
    notPickedUp,
}: DashboardProps) {
    const { auth, alerts, pollingIntervalSeconds } = usePage().props;
    const { formatDate, formatTime } = useDateFormat();

    // Daftar ikut diperbarui bersama badge agar jumlah dan isinya selalu cocok.
    usePoll(pollingIntervalSeconds * SECOND_MS, {
        only: [
            'alerts',
            'now',
            'fleet',
            'pickupsToday',
            'returnsToday',
            'overdue',
            'notPickedUp',
        ],
    });

    const time = (value: string) => (
        <span className="font-medium tabular-nums">{formatTime(value)}</span>
    );
    const status = (booking: Booking) => (
        <StatusBadge status={booking.display_status} />
    );

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Dashboard"
                    description={`Selamat datang, ${auth.user.name}. Berikut ringkasan armada dan jadwal peminjaman per ${formatDate(now)}.`}
                    actions={
                        <Button asChild>
                            <Link href={create()}>
                                <Plus aria-hidden="true" />
                                Jadwalkan Peminjaman
                            </Link>
                        </Button>
                    }
                />

                <FleetSummary items={fleet} />

                <div className="grid gap-4 lg:grid-cols-2">
                    <BookingListCard
                        title="Terlambat"
                        description="Peminjaman yang melewati rencana waktu kembali dan perlu segera ditindaklanjuti dengan karyawan."
                        icon={AlarmClockOff}
                        tone="destructive"
                        bookings={overdue}
                        total={alerts?.overdue}
                        limit={alertListLimit}
                        viewAllHref={bookings({
                            query: { status: ['overdue'] },
                        })}
                        emptyText="Tidak ada peminjaman yang terlambat."
                        renderDetail={(booking) => (
                            <span className="inline-flex items-center gap-1 text-xs text-muted-foreground tabular-nums">
                                <Phone className="size-3" aria-hidden="true" />
                                {booking.employee.phone}
                            </span>
                        )}
                        renderMeta={(booking) => (
                            <div className="grid gap-0.5 text-xs">
                                <span className="font-medium text-destructive">
                                    {formatDuration(
                                        booking.planned_return_at,
                                        now,
                                    )}
                                </span>
                                <span className="text-muted-foreground">
                                    terlambat
                                </span>
                            </div>
                        )}
                    />

                    <BookingListCard
                        title="Belum Diambil"
                        description="Peminjaman yang sudah melewati waktu mulai tetapi mobilnya belum diserahterimakan."
                        icon={Clock}
                        tone="warning"
                        bookings={notPickedUp}
                        total={alerts?.not_picked_up}
                        limit={alertListLimit}
                        viewAllHref={bookings({
                            query: { status: ['not_picked_up'] },
                        })}
                        emptyText="Semua peminjaman yang sudah dimulai telah diserahterimakan."
                        renderMeta={(booking) => (
                            <div className="grid gap-0.5 text-xs">
                                <span className="font-medium text-warning">
                                    {formatDuration(booking.start_at, now)}
                                </span>
                                <span className="text-muted-foreground">
                                    lewat waktu mulai
                                </span>
                            </div>
                        )}
                    />

                    <BookingListCard
                        title="Jadwal Ambil Hari Ini"
                        description="Peminjaman terjadwal yang mobilnya akan diserahterimakan kepada karyawan hari ini."
                        icon={KeyRound}
                        bookings={pickupsToday}
                        emptyText="Tidak ada jadwal ambil hari ini."
                        renderMeta={(booking) => (
                            <div className="grid justify-items-end gap-1">
                                {time(booking.start_at)}
                                {status(booking)}
                            </div>
                        )}
                    />

                    <BookingListCard
                        title="Jadwal Kembali Hari Ini"
                        description="Peminjaman aktif yang mobilnya direncanakan kembali ke kantor hari ini."
                        icon={Undo2}
                        bookings={returnsToday}
                        emptyText="Tidak ada jadwal kembali hari ini."
                        renderMeta={(booking) => (
                            <div className="grid justify-items-end gap-1">
                                {time(booking.planned_return_at)}
                                {status(booking)}
                            </div>
                        )}
                    />
                </div>

                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CalendarClock className="size-3.5" aria-hidden="true" />
                    Diperbarui otomatis setiap {pollingIntervalSeconds} detik.
                </p>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};

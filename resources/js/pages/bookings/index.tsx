import { Head, Link } from '@inertiajs/react';
import { CalendarClock, Plus } from 'lucide-react';
import { BookingActionDialogs } from '@/components/bookings/booking-action-dialogs';
import { BookingActionsMenu } from '@/components/bookings/booking-actions-menu';
import { useBookingColumns } from '@/components/bookings/booking-columns';
import { DataTableCard } from '@/components/data-table/data-table-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useBookingDialogs } from '@/hooks/use-booking-dialogs';
import { useTableQuery } from '@/hooks/use-table-query';
import { create, index } from '@/routes/bookings';
import type { BookingIndexProps } from '@/types';

export default function BookingIndex({
    bookings,
    query,
    statuses,
    maxUploadKb,
}: BookingIndexProps) {
    const table = useTableQuery({
        url: index.url(),
        query,
        only: ['bookings', 'query'],
    });
    const columns = useBookingColumns();
    const dialogs = useBookingDialogs();

    return (
        <>
            <Head title="Peminjaman" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Peminjaman"
                    description="Kelola peminjaman yang dijadwalkan dan sedang berjalan, termasuk serah terima dan perpanjangannya."
                    actions={
                        <Button asChild data-test="create-booking-button">
                            <Link href={create()}>
                                <Plus aria-hidden="true" />
                                Jadwalkan Peminjaman
                            </Link>
                        </Button>
                    }
                />

                <DataTableCard
                    title="Daftar Peminjaman"
                    table={table}
                    data={bookings}
                    columns={columns}
                    rowKey={(booking) => booking.id}
                    rowActions={(booking) => (
                        <BookingActionsMenu
                            booking={booking}
                            onAction={dialogs.open}
                        />
                    )}
                    searchPlaceholder="Cari kode, karyawan, atau plat..."
                    filterGroups={[
                        { key: 'status', label: 'Status', options: statuses },
                    ]}
                    emptyState={{
                        icon: CalendarClock,
                        title: 'Belum ada peminjaman berjalan',
                        description:
                            'Jadwalkan peminjaman saat karyawan mengajukan pemakaian mobil dinas.',
                    }}
                />
            </div>

            <BookingActionDialogs dialogs={dialogs} maxUploadKb={maxUploadKb} />
        </>
    );
}

BookingIndex.layout = {
    breadcrumbs: [{ title: 'Peminjaman', href: index() }],
};

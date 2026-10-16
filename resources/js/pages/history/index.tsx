import { Head, Link } from '@inertiajs/react';
import { Eye, History } from 'lucide-react';
import { DataTableCard } from '@/components/data-table/data-table-card';
import { ExportMenu } from '@/components/history/export-menu';
import { useHistoryColumns } from '@/components/history/history-columns';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useTableQuery } from '@/hooks/use-table-query';
import { show } from '@/routes/bookings';
import { index } from '@/routes/history';
import type { HistoryIndexProps } from '@/types';

export default function HistoryIndex({
    bookings,
    query,
    filterOptions,
    exportFormats,
}: HistoryIndexProps) {
    const table = useTableQuery({
        url: index.url(),
        query,
        only: ['bookings', 'query'],
    });
    const columns = useHistoryColumns();

    return (
        <>
            <Head title="Riwayat" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Riwayat"
                    description="Semua peminjaman beserta keterlambatannya. Ekspor mengikuti pencarian, filter, dan periode yang dipilih."
                    actions={
                        <ExportMenu query={query} formats={exportFormats} />
                    }
                />

                <DataTableCard
                    title="Riwayat Peminjaman"
                    description="Periode dihitung dari waktu mulai peminjaman."
                    table={table}
                    data={bookings}
                    columns={columns}
                    rowKey={(booking) => booking.id}
                    rowActions={(booking) => (
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            asChild
                            aria-label={`Lihat detail ${booking.code}`}
                        >
                            <Link href={show(booking.id)}>
                                <Eye aria-hidden="true" />
                            </Link>
                        </Button>
                    )}
                    searchPlaceholder="Cari kode, karyawan, NIP, atau plat..."
                    filterGroups={[
                        {
                            key: 'status',
                            label: 'Status',
                            options: filterOptions.status,
                        },
                        {
                            key: 'lateness',
                            label: 'Keterlambatan',
                            options: filterOptions.lateness,
                        },
                        {
                            key: 'division',
                            label: 'Divisi',
                            options: filterOptions.division,
                        },
                        {
                            key: 'car',
                            label: 'Mobil',
                            options: filterOptions.car,
                            submenu: true,
                        },
                        {
                            key: 'employee',
                            label: 'Karyawan',
                            options: filterOptions.employee,
                            submenu: true,
                        },
                    ]}
                    emptyState={{
                        icon: History,
                        title: 'Belum ada riwayat peminjaman',
                        description:
                            'Peminjaman yang dijadwalkan akan tercatat di sini.',
                    }}
                />
            </div>
        </>
    );
}

HistoryIndex.layout = {
    breadcrumbs: [{ title: 'Riwayat', href: index() }],
};

import { TruncatedText } from '@/components/data-table/truncated-text';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import { useDateFormat } from '@/hooks/use-date-format';
import { formatMinutes } from '@/lib/datetime';
import type { HistoryBooking } from '@/types';

function Empty() {
    return <span className="text-muted-foreground">—</span>;
}

export function useHistoryColumns(): DataTableColumn<HistoryBooking>[] {
    const { formatDateTime } = useDateFormat();

    return [
        {
            id: 'code',
            header: 'Kode',
            cell: (booking) => (
                <span className="font-medium tabular-nums">{booking.code}</span>
            ),
        },
        {
            id: 'purpose',
            header: 'Tujuan',
            cell: (booking) => (
                <TruncatedText text={booking.purpose} className="max-w-56" />
            ),
        },
        {
            id: 'employee',
            header: 'Karyawan',
            cell: (booking) => (
                <TruncatedText
                    text={booking.employee.name}
                    className="max-w-48 font-medium"
                />
            ),
        },
        {
            id: 'division',
            header: 'Divisi',
            cell: (booking) => booking.employee.division,
        },
        {
            id: 'plate',
            header: 'Plat',
            cell: (booking) => booking.car.plate_number,
        },
        {
            id: 'car',
            header: 'Mobil',
            cell: (booking) => (
                <TruncatedText
                    text={`${booking.car.brand} ${booking.car.model}`}
                    className="max-w-44"
                />
            ),
        },
        {
            id: 'start_at',
            header: 'Mulai',
            skeleton: 'date',
            cell: (booking) => formatDateTime(booking.start_at),
        },
        {
            id: 'planned_return_at',
            header: 'Rencana kembali',
            skeleton: 'date',
            cell: (booking) => formatDateTime(booking.planned_return_at),
        },
        {
            id: 'returned_at',
            header: 'Dikembalikan',
            skeleton: 'date',
            cell: (booking) =>
                booking.returned_at ? (
                    formatDateTime(booking.returned_at)
                ) : (
                    <Empty />
                ),
        },
        {
            id: 'status',
            header: 'Status',
            skeleton: 'badge',
            cell: (booking) => <StatusBadge status={booking.display_status} />,
        },
        {
            id: 'lateness',
            header: 'Keterlambatan',
            align: 'right',
            cell: (booking) =>
                booking.lateness_minutes !== null ? (
                    <span className="font-medium text-destructive">
                        {formatMinutes(booking.lateness_minutes)}
                    </span>
                ) : (
                    <Empty />
                ),
        },
        {
            id: 'extensions',
            header: 'Perpanjangan',
            align: 'right',
            cell: (booking) =>
                booking.extensions_count > 0 ? (
                    `${booking.extensions_count}×`
                ) : (
                    <Empty />
                ),
        },
    ];
}

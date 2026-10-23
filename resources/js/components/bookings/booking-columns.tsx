import { TriangleAlert } from 'lucide-react';
import { CellNote } from '@/components/data-table/cell-note';
import { TruncatedText } from '@/components/data-table/truncated-text';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import { useDateFormat } from '@/hooks/use-date-format';
import type { Booking } from '@/types';

export function useBookingColumns(): DataTableColumn<Booking>[] {
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
            id: 'plate',
            header: 'Plat',
            cell: (booking) => (
                <span className="tabular-nums">{booking.car.plate_number}</span>
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
            id: 'status',
            header: 'Status',
            skeleton: 'badge',
            cell: (booking) => (
                <span className="inline-flex items-center gap-1.5">
                    <StatusBadge status={booking.display_status} />
                    {booking.awaiting_return_code && (
                        <CellNote
                            icon={TriangleAlert}
                            text={`Mobil belum kembali dari ${booking.awaiting_return_code}`}
                        />
                    )}
                </span>
            ),
        },
    ];
}

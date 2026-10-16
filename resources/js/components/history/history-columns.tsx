import { CalendarPlus } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import { useDateFormat } from '@/hooks/use-date-format';
import { formatMinutes } from '@/lib/datetime';
import type { HistoryBooking } from '@/types';

export function useHistoryColumns(): DataTableColumn<HistoryBooking>[] {
    const { formatDateTime } = useDateFormat();

    return [
        {
            id: 'code',
            header: 'Kode',
            cell: (booking) => (
                <div className="max-w-48">
                    <div className="font-medium tabular-nums">
                        {booking.code}
                    </div>
                    <div className="truncate text-xs text-muted-foreground">
                        {booking.purpose}
                    </div>
                </div>
            ),
        },
        {
            id: 'employee',
            header: 'Karyawan',
            cell: (booking) => (
                <div>
                    <div className="font-medium">{booking.employee.name}</div>
                    <div className="text-xs text-muted-foreground tabular-nums">
                        {booking.employee.employee_number} ·{' '}
                        {booking.employee.division}
                    </div>
                </div>
            ),
        },
        {
            id: 'car',
            header: 'Mobil',
            cell: (booking) => (
                <div>
                    <div className="font-medium">
                        {booking.car.plate_number}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {booking.car.brand} {booking.car.model}
                    </div>
                </div>
            ),
        },
        {
            id: 'schedule',
            header: 'Waktu',
            cell: (booking) => (
                <div className="text-sm whitespace-nowrap tabular-nums">
                    <div>{formatDateTime(booking.start_at)}</div>
                    <div className="text-muted-foreground">
                        {booking.returned_at
                            ? `kembali ${formatDateTime(booking.returned_at)}`
                            : `rencana ${formatDateTime(booking.planned_return_at)}`}
                    </div>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (booking) => (
                // flex, bukan grid: badge ber-overflow-hidden di dalam grid
                // dianggap selebar 0 sehingga menimpa kolom di sebelahnya.
                <div className="flex flex-col items-start gap-1">
                    <StatusBadge status={booking.display_status} />
                    {booking.lateness_minutes !== null && (
                        <span className="text-xs font-medium text-destructive">
                            Terlambat {formatMinutes(booking.lateness_minutes)}
                        </span>
                    )}
                    {booking.extensions_count > 0 && (
                        <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <CalendarPlus
                                className="size-3.5"
                                aria-hidden="true"
                            />
                            Diperpanjang {booking.extensions_count}×
                        </span>
                    )}
                </div>
            ),
        },
    ];
}

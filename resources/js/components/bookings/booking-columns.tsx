import { AwaitingReturnHint } from '@/components/bookings/awaiting-return-notice';
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
                <div className="max-w-56">
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
                    <div className="text-xs text-muted-foreground">
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
            header: 'Jadwal',
            cell: (booking) => (
                <div className="text-sm whitespace-nowrap tabular-nums">
                    <div>{formatDateTime(booking.start_at)}</div>
                    <div className="text-muted-foreground">
                        s.d. {formatDateTime(booking.planned_return_at)}
                    </div>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (booking) => (
                <div className="grid justify-items-start gap-1">
                    <StatusBadge status={booking.display_status} />
                    {booking.awaiting_return_code && (
                        <AwaitingReturnHint
                            code={booking.awaiting_return_code}
                        />
                    )}
                </div>
            ),
        },
    ];
}

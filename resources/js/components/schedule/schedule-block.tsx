import { Link } from '@inertiajs/react';
import { statusToneClasses } from '@/components/status-badge';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useDateFormat } from '@/hooks/use-date-format';
import { cn } from '@/lib/utils';
import type { TimelineBlock } from '@/lib/timeline';
import { show } from '@/routes/bookings';

/** Tinggi satu lajur (px); dipakai juga untuk menghitung tinggi baris. */
export const LANE_HEIGHT_PX = 36;
export const ROW_PADDING_PX = 6;

/**
 * Di bawah lebar ini (± 10 jam, sekitar 50 px pada layar laptop) nama
 * karyawan hanya akan tampil sebagai satu-dua huruf terpotong.
 */
const MIN_LABEL_WIDTH_PERCENT = 6;

/**
 * Satu peminjaman di Jadwal Armada. Warna mengikuti status (StatusBadge) dan
 * disertai nama karyawan (atau tooltip untuk blok sempit), sehingga tidak
 * bergantung pada warna saja.
 */
export function ScheduleBlock({ block }: { block: TimelineBlock }) {
    const { formatDateTime } = useDateFormat();
    const { booking } = block;
    const range = `${formatDateTime(booking.start_at)} – ${formatDateTime(booking.end_at)}`;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Link
                    href={show(booking.id)}
                    // Lapisan bg-card membuat blok buram, sehingga garis hari
                    // di belakangnya tidak tembus dan teks tetap terbaca.
                    className={cn(
                        'absolute h-8 min-w-6 overflow-hidden rounded-md bg-card focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        block.continuesBefore && 'rounded-l-none',
                        block.continuesAfter && 'rounded-r-none',
                    )}
                    style={{
                        left: `${block.left}%`,
                        width: `${block.width}%`,
                        top: block.lane * LANE_HEIGHT_PX + ROW_PADDING_PX,
                    }}
                    aria-label={`${booking.code}, ${booking.employee_name}, ${booking.display_status_label}, ${range}`}
                    data-test="schedule-block"
                >
                    <span
                        className={cn(
                            'flex size-full items-center border border-current/25 px-2 text-xs font-medium whitespace-nowrap transition-[filter] hover:brightness-95 dark:hover:brightness-125',
                            statusToneClasses(booking.display_status),
                            block.continuesBefore
                                ? 'border-l-0'
                                : 'rounded-l-md',
                            block.continuesAfter
                                ? 'border-r-0'
                                : 'rounded-r-md',
                        )}
                    >
                        {/* Blok yang terlalu sempit hanya berwarna; detail ada di tooltip. */}
                        {block.width >= MIN_LABEL_WIDTH_PERCENT && (
                            <span className="truncate">
                                {booking.employee_name}
                            </span>
                        )}
                    </span>
                </Link>
            </TooltipTrigger>
            <TooltipContent side="top" className="grid max-w-72 gap-0.5">
                <span className="font-medium">
                    {booking.code} · {booking.display_status_label}
                </span>
                <span>{booking.employee_name}</span>
                <span>{booking.purpose}</span>
                <span className="tabular-nums">{range}</span>
            </TooltipContent>
        </Tooltip>
    );
}

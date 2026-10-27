import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { memo, type ReactNode } from 'react';
import { AwaitingReturnHint } from '@/components/bookings/awaiting-return-notice';
import { ListSkeleton } from '@/components/skeletons/list-skeleton';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import { cn } from '@/lib/utils';
import { show } from '@/routes/bookings';
import type { Booking } from '@/types';

type Props = {
    title: string;
    description: string;
    icon: LucideIcon;
    /** undefined = masih dimuat (deferred prop). */
    bookings: Booking[] | undefined;
    /** Jumlah keseluruhan bila daftar dipotong (mis. dari badge). */
    total?: number;
    /** Batas baris daftar; dipakai untuk jumlah baris skeleton. */
    limit?: number;
    emptyText: string;
    /** Info utama di kanan tiap baris, mis. jam atau lama keterlambatan. */
    renderMeta: (booking: Booking) => ReactNode;
    /** Info tambahan di bawah nama pegawai, mis. no HP. */
    renderDetail?: (booking: Booking) => ReactNode;
    tone?: 'default' | 'destructive' | 'warning';
};

const toneClasses = {
    default: 'bg-muted text-muted-foreground',
    destructive: 'bg-destructive/10 text-destructive dark:bg-destructive/20',
    warning: 'bg-warning/10 text-warning dark:bg-warning/20',
} as const;

const badgeToneClasses = {
    default: 'bg-muted text-muted-foreground border-transparent',
    destructive:
        'bg-destructive/10 text-destructive border-transparent dark:bg-destructive/20',
    warning: 'bg-warning/15 text-warning border-transparent dark:bg-warning/25',
} as const;

function getInitials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

/**
 * Kartu daftar peminjaman di dashboard. Setiap baris tertaut ke detail
 * peminjaman (FR-20). Selama daftar dimuat, skeleton menempati ruang yang
 * sama: jumlah barisnya diambil dari badge (min(total, limit)).
 */
export const BookingListCard = memo(function BookingListCard({
    title,
    description,
    icon: Icon,
    bookings,
    total,
    limit,
    emptyText,
    renderMeta,
    renderDetail,
    tone = 'default',
}: Props) {
    const loading = bookings === undefined;
    const showSkeleton = useDelayedFlag(loading);
    const count = total ?? bookings?.length ?? 0;
    const truncated = !loading && count > bookings.length;
    const skeletonRows = Math.max(1, Math.min(count, limit ?? count));

    return (
        <Card className="gap-0 rounded-xl pb-0 shadow-xs">
            <CardHeader className="border-b pb-4">
                <CardTitle className="flex items-center gap-2">
                    <span
                        className={cn(
                            'flex size-7 items-center justify-center rounded-md',
                            toneClasses[tone],
                        )}
                    >
                        <Icon className="size-4" aria-hidden="true" />
                    </span>
                    <span>{title}</span>
                    <Badge
                        variant="outline"
                        className={cn(
                            'font-semibold tabular-nums',
                            badgeToneClasses[tone],
                        )}
                        aria-label={`${count} peminjaman`}
                    >
                        {count}
                    </Badge>
                </CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="px-0" aria-busy={loading}>
                {loading && count > 0 ? (
                    // Pemuatan singkat: ruang tetap disiapkan tanpa skeleton.
                    <div className={cn(!showSkeleton && 'invisible')}>
                        <ListSkeleton
                            rows={skeletonRows}
                            detailLine={renderDetail !== undefined}
                        />
                    </div>
                ) : !bookings || bookings.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-2 px-6 py-8 text-center">
                        <div className="flex size-9 items-center justify-center rounded-full bg-muted text-muted-foreground">
                            <Icon className="size-5" aria-hidden="true" />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {emptyText}
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y divide-border">
                        {bookings.map((booking) => (
                            <li key={booking.id}>
                                <Link
                                    href={show(booking.id)}
                                    className="flex items-center gap-3 px-6 py-3 text-sm transition-colors hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
                                >
                                    <div
                                        className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground"
                                        aria-hidden="true"
                                    >
                                        {getInitials(booking.employee.name)}
                                    </div>
                                    <div className="grid min-w-0 flex-1 gap-0.5">
                                        <span className="truncate font-medium text-foreground">
                                            {booking.employee.name}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {booking.code} · {booking.car.brand}{' '}
                                            {booking.car.model} (
                                            {booking.car.plate_number})
                                        </span>
                                        {renderDetail?.(booking)}
                                        {booking.awaiting_return_code && (
                                            <AwaitingReturnHint
                                                code={
                                                    booking.awaiting_return_code
                                                }
                                                className="max-w-full"
                                            />
                                        )}
                                    </div>
                                    <div className="shrink-0 text-right">
                                        {renderMeta(booking)}
                                    </div>
                                    <ChevronRight
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                {truncated && (
                    <p className="border-t px-6 py-3 text-xs text-muted-foreground">
                        Menampilkan {bookings.length} dari {count} peminjaman.
                    </p>
                )}
            </CardContent>
        </Card>
    );
});

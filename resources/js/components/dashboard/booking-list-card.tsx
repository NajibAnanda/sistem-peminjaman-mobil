import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { AwaitingReturnHint } from '@/components/bookings/awaiting-return-notice';
import { ListSkeleton } from '@/components/skeletons/list-skeleton';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
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
    viewAllHref?: NonNullable<InertiaLinkProps['href']>;
    emptyText: string;
    /** Info utama di kanan tiap baris, mis. jam atau lama keterlambatan. */
    renderMeta: (booking: Booking) => ReactNode;
    /** Info tambahan di bawah nama karyawan, mis. no HP. */
    renderDetail?: (booking: Booking) => ReactNode;
    tone?: 'default' | 'destructive' | 'warning';
};

const toneClasses = {
    default: 'bg-muted text-muted-foreground',
    destructive: 'bg-destructive/10 text-destructive dark:bg-destructive/20',
    warning: 'bg-warning/10 text-warning dark:bg-warning/20',
} as const;

/**
 * Kartu daftar peminjaman di dashboard. Setiap baris tertaut ke detail
 * peminjaman (FR-20). Selama daftar dimuat, skeleton menempati ruang yang
 * sama: jumlah barisnya diambil dari badge (min(total, limit)).
 */
export function BookingListCard({
    title,
    description,
    icon: Icon,
    bookings,
    total,
    limit,
    viewAllHref,
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
        <Card className="gap-0 pb-0">
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
                    {title}
                    <Badge variant="secondary" className="tabular-nums">
                        {count}
                    </Badge>
                </CardTitle>
                <CardDescription>{description}</CardDescription>
                {viewAllHref && count > 0 && (
                    <CardAction>
                        <Link
                            href={viewAllHref}
                            className="text-sm text-primary underline-offset-4 hover:underline"
                        >
                            Lihat semua
                        </Link>
                    </CardAction>
                )}
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
                    <p className="px-6 py-8 text-center text-sm text-muted-foreground">
                        {emptyText}
                    </p>
                ) : (
                    <ul className="divide-y divide-border">
                        {bookings.map((booking) => (
                            <li key={booking.id}>
                                <Link
                                    href={show(booking.id)}
                                    className="flex items-center gap-3 px-6 py-3 text-sm transition-colors hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                                >
                                    <div className="grid min-w-0 flex-1 gap-0.5">
                                        <span className="truncate font-medium">
                                            {booking.employee.name}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {booking.code} ·{' '}
                                            {booking.car.plate_number}
                                        </span>
                                        {renderDetail?.(booking)}
                                        {booking.awaiting_return_code && (
                                            <AwaitingReturnHint
                                                code={
                                                    booking.awaiting_return_code
                                                }
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
                        Menampilkan {bookings.length} dari {count}. Buka
                        &ldquo;Lihat semua&rdquo; untuk daftar lengkap.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

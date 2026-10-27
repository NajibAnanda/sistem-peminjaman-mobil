import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import { AlarmClockOff, CarFront, Wrench } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { CardStatSkeleton } from '@/components/skeletons/card-stat-skeleton';
import { Card } from '@/components/ui/card';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import { cn } from '@/lib/utils';
import { index as bookings } from '@/routes/bookings';
import { index as cars } from '@/routes/cars';
import type { BookingAlerts, FleetSummaryItem } from '@/types';

type Props = {
    /** undefined = masih dimuat (deferred prop). */
    items: FleetSummaryItem[] | undefined;
    alerts?: BookingAlerts | null;
};

// 3 kartu: Armada Operasional, Perawatan & Nonaktif, Peringatan Jadwal.
const CARD_COUNT = 3;

/**
 * Ringkasan statistik operasional armada dan peringatan (FR-20)
 * dalam format kartu multi-segmen proporsional.
 */
export function FleetSummary({ items, alerts }: Props) {
    const page = usePage();
    const currentAlerts = alerts ?? page.props.alerts;
    const showSkeleton = useDelayedFlag(items === undefined);

    if (items === undefined) {
        return (
            <div
                className={cn(
                    'grid gap-4 md:grid-cols-3',
                    !showSkeleton && 'invisible',
                )}
                aria-busy="true"
            >
                {Array.from({ length: CARD_COUNT }, (_, index) => (
                    <CardStatSkeleton key={index} />
                ))}
            </div>
        );
    }

    // 1. Data Armada Operasional (Tersedia & Dipinjam)
    const countAvailable =
        items.find((i) => i.status === 'available')?.count ?? 0;
    const countBorrowed =
        items.find((i) => i.status === 'borrowed')?.count ?? 0;
    const totalOperational = countAvailable + countBorrowed;
    const pctAvailable =
        totalOperational > 0
            ? Math.round((countAvailable / totalOperational) * 100)
            : 0;
    const pctBorrowed = totalOperational > 0 ? 100 - pctAvailable : 0;

    // 2. Data Kendala & Perawatan (Maintenance & Nonaktif)
    const countMaintenance =
        items.find((i) => i.status === 'maintenance')?.count ?? 0;
    const countInactive =
        items.find((i) => i.status === 'inactive')?.count ?? 0;
    const totalTroubled = countMaintenance + countInactive;
    const pctMaintenance =
        totalTroubled > 0
            ? Math.round((countMaintenance / totalTroubled) * 100)
            : 0;
    const pctInactive = totalTroubled > 0 ? 100 - pctMaintenance : 0;

    // 3. Data Peringatan & Tindak Lanjut (Terlambat & Belum Diambil)
    const countOverdue = currentAlerts?.overdue ?? 0;
    const countNotPickedUp = currentAlerts?.not_picked_up ?? 0;
    const totalAlerts = countOverdue + countNotPickedUp;
    const pctOverdue =
        totalAlerts > 0 ? Math.round((countOverdue / totalAlerts) * 100) : 0;
    const pctNotPickedUp = totalAlerts > 0 ? 100 - pctOverdue : 0;

    return (
        <div className="grid gap-4 md:grid-cols-3">
            {/* Card 1: Armada Operasional */}
            <StatCard
                title="Armada Operasional"
                href={cars({ query: { status: ['available', 'borrowed'] } })}
                icon={CarFront}
                count={totalOperational}
                unit="mobil"
                ariaLabel={`${totalOperational} mobil operasional: ${countAvailable} tersedia, ${countBorrowed} dipinjam`}
                segments={
                    totalOperational === 0
                        ? [{ width: 100, className: 'bg-muted' }]
                        : [
                              { width: pctAvailable, className: 'bg-success' },
                              { width: pctBorrowed, className: 'bg-primary' },
                          ]
                }
                legends={[
                    {
                        dotClass: 'bg-success',
                        count: countAvailable,
                        label: 'Tersedia',
                    },
                    {
                        dotClass: 'bg-primary',
                        count: countBorrowed,
                        label: 'Dipinjam',
                    },
                ]}
            />

            {/* Card 2: Perawatan & Nonaktif */}
            <StatCard
                title="Perawatan & Nonaktif"
                href={cars({ query: { status: ['maintenance', 'inactive'] } })}
                icon={Wrench}
                count={totalTroubled}
                unit="mobil"
                ariaLabel={`${totalTroubled} mobil perawatan: ${countMaintenance} maintenance, ${countInactive} nonaktif`}
                segments={
                    totalTroubled === 0
                        ? [{ width: 100, className: 'bg-muted' }]
                        : [
                              {
                                  width: pctMaintenance,
                                  className: 'bg-warning',
                              },
                              {
                                  width: pctInactive,
                                  className: 'bg-muted-foreground/40',
                              },
                          ]
                }
                legends={[
                    {
                        dotClass: 'bg-warning',
                        count: countMaintenance,
                        label: 'Maintenance',
                    },
                    {
                        dotClass: 'bg-muted-foreground/40',
                        count: countInactive,
                        label: 'Nonaktif',
                    },
                ]}
            />

            {/* Card 3: Peringatan Jadwal */}
            <StatCard
                title="Peringatan Jadwal"
                href={bookings({
                    query: { status: ['overdue', 'not_picked_up'] },
                })}
                icon={AlarmClockOff}
                count={totalAlerts}
                unit="peminjaman"
                ariaLabel={`${totalAlerts} peringatan jadwal: ${countOverdue} terlambat, ${countNotPickedUp} belum diambil`}
                segments={
                    totalAlerts === 0
                        ? [{ width: 100, className: 'bg-muted' }]
                        : [
                              {
                                  width: pctOverdue,
                                  className: 'bg-destructive',
                              },
                              {
                                  width: pctNotPickedUp,
                                  className: 'bg-warning',
                              },
                          ]
                }
                legends={[
                    {
                        dotClass: 'bg-destructive',
                        count: countOverdue,
                        label: 'Terlambat',
                    },
                    {
                        dotClass: 'bg-warning',
                        count: countNotPickedUp,
                        label: 'Belum Diambil',
                    },
                ]}
            />
        </div>
    );
}

type Segment = {
    width: number;
    className: string;
};

type LegendItem = {
    dotClass: string;
    count?: number;
    label: string;
};

type StatCardProps = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
    count: number;
    unit: string;
    ariaLabel: string;
    segments: Segment[];
    legends: LegendItem[];
};

function StatCard({
    title,
    href,
    icon: Icon,
    count,
    unit,
    ariaLabel,
    segments,
    legends,
}: StatCardProps) {
    return (
        <Link
            href={href}
            className="block rounded-xl focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            aria-label={ariaLabel}
        >
            <Card className="gap-3.5 p-5 transition-colors hover:bg-muted/40 hover:shadow-xs">
                {/* Header: Judul & Ikon */}
                <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-muted-foreground">
                        {title}
                    </span>
                    <Icon
                        className="size-4 text-muted-foreground"
                        aria-hidden="true"
                    />
                </div>

                {/* Angka Metrik Utama */}
                <div className="flex items-baseline gap-1.5">
                    <span className="text-3xl font-bold tracking-tight tabular-nums">
                        {count}
                    </span>
                    <span className="text-sm font-medium text-muted-foreground">
                        {unit}
                    </span>
                </div>

                {/* Multi-segment Progress Bar */}
                <div
                    className="flex h-2 w-full overflow-hidden rounded-full bg-muted"
                    aria-hidden="true"
                >
                    {segments.map((seg, idx) =>
                        seg.width > 0 ? (
                            <div
                                key={idx}
                                className={cn(
                                    'h-full transition-[width] duration-500',
                                    seg.className,
                                )}
                                style={{ width: `${seg.width}%` }}
                            />
                        ) : null,
                    )}
                </div>

                {/* Legend di bawah Progress Bar */}
                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 pt-0.5 text-xs text-muted-foreground">
                    {legends.map((legend, idx) => (
                        <span
                            key={idx}
                            className="inline-flex items-center gap-1.5"
                        >
                            <span
                                className={cn(
                                    'size-2 shrink-0 rounded-full',
                                    legend.dotClass,
                                )}
                                aria-hidden="true"
                            />
                            {legend.count !== undefined && (
                                <span className="font-semibold text-foreground tabular-nums">
                                    {legend.count}
                                </span>
                            )}
                            <span>{legend.label}</span>
                        </span>
                    ))}
                </div>
            </Card>
        </Link>
    );
}

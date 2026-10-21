import { Link } from '@inertiajs/react';
import { CardStatSkeleton } from '@/components/skeletons/card-stat-skeleton';
import { StatusBadge } from '@/components/status-badge';
import { Card } from '@/components/ui/card';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import { cn } from '@/lib/utils';
import { index } from '@/routes/cars';
import type { FleetSummaryItem } from '@/types';

type Props = {
    /** undefined = masih dimuat (deferred prop). */
    items: FleetSummaryItem[] | undefined;
};

// Sama dengan jumlah App\Enums\CarDisplayStatus (Tersedia, Dipinjam,
// Maintenance, Nonaktif), agar skeleton menempati ruang yang sama.
const STATUS_COUNT = 4;

/**
 * Ringkasan armada per status tampilan (FR-20). Setiap kartu membuka Data
 * Mobil dengan filter status yang sama.
 */
export function FleetSummary({ items }: Props) {
    const showSkeleton = useDelayedFlag(items === undefined);

    if (items === undefined) {
        return (
            <div
                className={cn(
                    'grid gap-4 sm:grid-cols-2 xl:grid-cols-4',
                    !showSkeleton && 'invisible',
                )}
                aria-busy="true"
            >
                {Array.from({ length: STATUS_COUNT }, (_, index) => (
                    <CardStatSkeleton key={index} />
                ))}
            </div>
        );
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {items.map((item) => (
                <Link
                    key={item.status}
                    href={index({ query: { status: [item.status] } })}
                    className="rounded-xl focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label={`${item.count} mobil ${item.label}`}
                >
                    <Card className="gap-3 px-6 transition-colors hover:bg-muted/40">
                        <StatusBadge status={item.status} />
                        <div className="flex items-baseline gap-1.5">
                            <span className="text-3xl font-semibold tabular-nums">
                                {item.count}
                            </span>
                            <span className="text-sm text-muted-foreground">
                                mobil
                            </span>
                        </div>
                    </Card>
                </Link>
            ))}
        </div>
    );
}

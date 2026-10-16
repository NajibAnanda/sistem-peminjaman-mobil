import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/components/status-badge';
import { Card } from '@/components/ui/card';
import { index } from '@/routes/cars';
import type { FleetSummaryItem } from '@/types';

type Props = {
    items: FleetSummaryItem[];
};

/**
 * Ringkasan armada per status tampilan (FR-20). Setiap kartu membuka Data
 * Mobil dengan filter status yang sama.
 */
export function FleetSummary({ items }: Props) {
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

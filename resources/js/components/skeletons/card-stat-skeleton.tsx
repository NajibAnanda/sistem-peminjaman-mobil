import { LoadingText } from '@/components/skeletons/loading-text';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

/** Meniru kartu statistik multi-segmen (judul + ikon, angka besar, progress bar, dan legend). */
export function CardStatSkeleton() {
    return (
        <Card className="gap-3.5 p-5">
            <div className="grid gap-3.5" aria-hidden="true">
                <div className="flex items-center justify-between">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="size-4 rounded-sm" />
                </div>
                <div className="flex items-baseline gap-1.5">
                    <Skeleton className="h-8 w-14" />
                    <Skeleton className="h-4 w-10" />
                </div>
                <Skeleton className="h-2 w-full rounded-full" />
                <div className="flex items-center gap-4 pt-0.5">
                    <Skeleton className="h-3.5 w-24" />
                    <Skeleton className="h-3.5 w-24" />
                </div>
            </div>
            <LoadingText />
        </Card>
    );
}

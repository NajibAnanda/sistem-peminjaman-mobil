import { LoadingText } from '@/components/skeletons/loading-text';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

/** Meniru kartu ringkasan armada (badge status + angka besar). */
export function CardStatSkeleton() {
    return (
        <Card className="gap-3 px-6">
            <div className="grid gap-3" aria-hidden="true">
                <Skeleton className="h-5 w-24 rounded-4xl" />
                <div className="flex h-9 items-center gap-1.5">
                    <Skeleton className="h-8 w-10" />
                    <Skeleton className="h-4 w-12" />
                </div>
            </div>
            <LoadingText />
        </Card>
    );
}

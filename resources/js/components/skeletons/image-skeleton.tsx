import { LoadingText } from '@/components/skeletons/loading-text';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

/**
 * Menutup seluruh bingkai foto selama gambar dimuat. Bingkai induk yang
 * menentukan ukuran/rasio aspek, sehingga tidak ada pergeseran tata letak.
 */
export function ImageSkeleton({ className }: { className?: string }) {
    return (
        <span className={cn('absolute inset-0', className)}>
            <Skeleton className="size-full rounded-none" aria-hidden="true" />
            <LoadingText>Memuat foto...</LoadingText>
        </span>
    );
}

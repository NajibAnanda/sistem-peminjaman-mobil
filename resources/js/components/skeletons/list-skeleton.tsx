import { LoadingText } from '@/components/skeletons/loading-text';
import { Skeleton } from '@/components/ui/skeleton';

type Props = {
    rows: number;
    /** Baris berisi keterangan tambahan (mis. no HP) di bawah kode. */
    detailLine?: boolean;
};

/**
 * Meniru baris daftar peminjaman di dashboard: nama + kode di kiri,
 * keterangan waktu di kanan, dengan tinggi baris yang sama.
 */
export function ListSkeleton({ rows, detailLine = false }: Props) {
    return (
        <div>
            <ul className="divide-y divide-border" aria-hidden="true">
                {Array.from({ length: rows }, (_, index) => (
                    <li
                        key={index}
                        className="flex items-center gap-3 px-6 py-3"
                    >
                        <Skeleton className="size-8 shrink-0 rounded-full" />
                        <div className="grid min-w-0 flex-1 gap-0.5">
                            <div className="flex h-5 items-center">
                                <Skeleton className="h-4 w-40 max-w-full" />
                            </div>
                            <div className="flex h-4 items-center">
                                <Skeleton className="h-3 w-32 max-w-full" />
                            </div>
                            {detailLine && (
                                <div className="flex h-4 items-center">
                                    <Skeleton className="h-3 w-24 max-w-full" />
                                </div>
                            )}
                        </div>
                        <div className="grid justify-items-end gap-1">
                            <Skeleton className="h-4 w-16" />
                            <Skeleton className="h-3 w-12" />
                        </div>
                        <Skeleton className="size-4 shrink-0" />
                    </li>
                ))}
            </ul>
            <LoadingText />
        </div>
    );
}

import { LoadingText } from '@/components/skeletons/loading-text';
import { Skeleton } from '@/components/ui/skeleton';

type Props = {
    rows?: number;
    /** Tampilkan baris judul grup seperti hasil pencarian global. */
    heading?: boolean;
};

const DEFAULT_ROWS = 3;

/** Meniru item hasil pencarian/combobox: ikon + judul + keterangan. */
export function SearchResultSkeleton({
    rows = DEFAULT_ROWS,
    heading = false,
}: Props) {
    return (
        <div className="p-1">
            <div aria-hidden="true">
                {heading && (
                    <div className="flex h-7 items-center px-2">
                        <Skeleton className="h-3 w-20" />
                    </div>
                )}
                {Array.from({ length: rows }, (_, index) => (
                    <div
                        key={index}
                        className="flex items-center gap-2 px-2 py-1.5"
                    >
                        <Skeleton className="size-4 shrink-0" />
                        <div className="grid flex-1 gap-1">
                            <Skeleton className="h-3.5 w-2/5" />
                            <Skeleton className="h-3 w-3/5" />
                        </div>
                    </div>
                ))}
            </div>
            <LoadingText>Mencari...</LoadingText>
        </div>
    );
}

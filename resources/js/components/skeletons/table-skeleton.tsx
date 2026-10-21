import { LoadingText } from '@/components/skeletons/loading-text';
import { Skeleton } from '@/components/ui/skeleton';
import { TableCell, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

/** Bentuk isi sel saat memuat; sebaiknya sama dengan isi aslinya. */
export type TableSkeletonKind =
    | 'index'
    | 'text'
    | 'date'
    | 'number'
    | 'badge'
    | 'thumbnail'
    | 'action';

export type TableSkeletonColumn = {
    key: string;
    /** Kelas sel yang sama dengan sel data (padding tepi, perataan). */
    className?: string;
    /** Sama dengan perataan isi kolom. */
    align?: 'left' | 'right' | 'center';
    kind: TableSkeletonKind;
};

// Tinggi baris sama dengan baris data: teks sel bertinggi baris 20px (h-5).
const SHAPES: Record<TableSkeletonKind, string> = {
    index: 'h-4 w-5',
    text: 'h-4 w-28',
    date: 'h-4 w-32',
    number: 'ml-auto h-4 w-10',
    badge: 'h-5 w-20 rounded-4xl',
    thumbnail: 'h-10 w-14',
    action: 'mx-auto size-7',
};

type Props = {
    columns: TableSkeletonColumn[];
    rows: number;
};

/**
 * Baris pengganti isi tabel saat pencarian/filter/pindah halaman. Hanya
 * isi tabel yang diganti; header card, filter, dan footer tetap bisa dipakai.
 */
export function TableSkeleton({ columns, rows }: Props) {
    return Array.from({ length: rows }, (_, rowIndex) => (
        <TableRow key={`skeleton-${rowIndex}`} data-test="table-skeleton-row">
            {columns.map((column, columnIndex) => (
                <TableCell key={column.key} className={column.className}>
                    <div
                        className={cn(
                            'flex min-h-5 items-center',
                            column.align === 'center' && 'justify-center',
                            column.align === 'right' && 'justify-end',
                        )}
                    >
                        <Skeleton
                            className={SHAPES[column.kind]}
                            aria-hidden="true"
                        />
                    </div>
                    {rowIndex === 0 && columnIndex === 0 && <LoadingText />}
                </TableCell>
            ))}
        </TableRow>
    ));
}

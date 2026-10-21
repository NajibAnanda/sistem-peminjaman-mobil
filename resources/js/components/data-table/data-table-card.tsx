import { RotateCcw, SearchX } from 'lucide-react';
import type { Key, ReactNode } from 'react';
import { DataTableHeader } from '@/components/data-table/data-table-header';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { EDGE_LEFT, EDGE_RIGHT } from '@/components/data-table/layout';
import type {
    DataTableColumn,
    DataTableEmptyState,
    DataTableFilterGroup,
} from '@/components/data-table/types';
import { EmptyState } from '@/components/empty-state';
import type { TableSkeletonColumn } from '@/components/skeletons/table-skeleton';
import { TableSkeleton } from '@/components/skeletons/table-skeleton';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import type { UseTableQueryReturn } from '@/hooks/use-table-query';
import { rowNumber } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

// Kolom "#" selebar isinya, rata kiri sejajar judul tabel dan info halaman.
const NUMBER_COLUMN = cn('w-px text-left tabular-nums', EDGE_LEFT);
// Kolom aksi selebar isinya, rata tengah; tepi kanannya sejajar tombol ».
const ACTION_COLUMN = cn('w-px text-center', EDGE_RIGHT);

type Props<T> = {
    /** Judul tabel di header card, mis. "Daftar Petugas". */
    title: string;
    description?: string;
    table: UseTableQueryReturn;
    data: Paginated<T>;
    columns: DataTableColumn<T>[];
    rowKey: (row: T) => Key;
    /** Isi kolom "Aksi"; gunakan komponen RowActions. */
    rowActions?: (row: T) => ReactNode;
    searchPlaceholder: string;
    filterGroups?: DataTableFilterGroup[];
    /** Aksi tambahan di header card, di kanan tombol Filter (mis. Ekspor). */
    toolbar?: ReactNode;
    emptyState: DataTableEmptyState;
};

/**
 * Pola tabel standar aplikasi (AGENTS.md 8.4). Tata letak, perataan, dan
 * jarak diatur sepenuhnya di sini; halaman hanya mengirim konfigurasi.
 */
export function DataTableCard<T>({
    title,
    description,
    table,
    data,
    columns,
    rowKey,
    rowActions,
    searchPlaceholder,
    filterGroups,
    toolbar,
    emptyState,
}: Props<T>) {
    const rows = data.data;
    // Pemuatan singkat tetap menampilkan data lama tanpa skeleton (anti-kedip).
    const showSkeleton = useDelayedFlag(table.loading);
    const isEmpty = rows.length === 0 && !showSkeleton;

    // Sel terakhir (kolom aksi, atau kolom data terakhir bila tanpa aksi)
    // mendapat jarak tepi kanan yang sama dengan header dan footer.
    const lastColumnIndex = columns.length - 1;
    const alignOf = (column: DataTableColumn<T>) =>
        column.align ?? (column.skeleton === 'badge' ? 'center' : 'left');
    const columnClass = (column: DataTableColumn<T>, index: number) =>
        cn(
            alignOf(column) === 'right' && 'text-right tabular-nums',
            alignOf(column) === 'center' && 'text-center',
            column.className,
            !rowActions && index === lastColumnIndex && EDGE_RIGHT,
        );

    const skeletonColumns: TableSkeletonColumn[] = [
        { key: '#', className: NUMBER_COLUMN, kind: 'index' },
        ...columns.map((column, index): TableSkeletonColumn => ({
            key: column.id,
            className: columnClass(column, index),
            align: alignOf(column),
            kind:
                column.skeleton ??
                (column.align === 'right' ? 'number' : 'text'),
        })),
        ...(rowActions
            ? [
                  {
                      key: 'actions',
                      className: ACTION_COLUMN,
                      align: 'center',
                      kind: 'action',
                  } satisfies TableSkeletonColumn,
              ]
            : []),
    ];

    return (
        <Card className="@container/table min-w-0 gap-0 py-0">
            <DataTableHeader
                title={title}
                description={description}
                table={table}
                searchPlaceholder={searchPlaceholder}
                filterGroups={filterGroups}
                toolbar={toolbar}
            />

            <div aria-busy={table.loading} data-test="table-region">
                {isEmpty ? (
                    table.isFiltered ? (
                        <EmptyState
                            icon={SearchX}
                            title="Tidak ada hasil"
                            description="Tidak ada data yang cocok dengan pencarian atau filter ini."
                            action={
                                <Button
                                    variant="outline"
                                    onClick={table.resetAll}
                                >
                                    <RotateCcw aria-hidden="true" />
                                    Reset pencarian & filter
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState {...emptyState} />
                    )
                ) : (
                    <Table>
                        <TableCaption className="sr-only">{title}</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead className={NUMBER_COLUMN}>
                                    #
                                </TableHead>
                                {columns.map((column, index) => (
                                    <TableHead
                                        key={column.id}
                                        className={columnClass(column, index)}
                                    >
                                        {column.header}
                                    </TableHead>
                                ))}
                                {rowActions && (
                                    <TableHead className={ACTION_COLUMN}>
                                        Aksi
                                    </TableHead>
                                )}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {showSkeleton ? (
                                <TableSkeleton
                                    columns={skeletonColumns}
                                    rows={data.meta.per_page}
                                />
                            ) : (
                                rows.map((row, rowIndex) => (
                                    <TableRow key={rowKey(row)}>
                                        <TableCell
                                            className={cn(
                                                NUMBER_COLUMN,
                                                'text-muted-foreground',
                                            )}
                                        >
                                            {rowNumber(data.meta, rowIndex)}
                                        </TableCell>
                                        {columns.map((column, index) => (
                                            <TableCell
                                                key={column.id}
                                                className={columnClass(
                                                    column,
                                                    index,
                                                )}
                                            >
                                                {column.cell(row)}
                                            </TableCell>
                                        ))}
                                        {rowActions && (
                                            <TableCell
                                                className={ACTION_COLUMN}
                                            >
                                                {rowActions(row)}
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                )}
            </div>

            <DataTablePagination
                meta={data.meta}
                onPageChange={table.goToPage}
                disabled={table.loading}
            />
        </Card>
    );
}

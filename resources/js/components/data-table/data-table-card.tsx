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
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { UseTableQueryReturn } from '@/hooks/use-table-query';
import { rowNumber } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const SKELETON_MIN_ROWS = 5;

// Kolom "#" selebar isinya agar nomor sejajar dengan judul tabel di kiri.
const NUMBER_COLUMN = cn('w-px text-right', EDGE_LEFT);

type Props<T> = {
    /** Judul tabel di header card, mis. "Daftar Petugas". */
    title: string;
    description?: string;
    table: UseTableQueryReturn;
    data: Paginated<T>;
    columns: DataTableColumn<T>[];
    rowKey: (row: T) => Key;
    rowActions?: (row: T) => ReactNode;
    searchPlaceholder: string;
    filterGroups?: DataTableFilterGroup[];
    emptyState: DataTableEmptyState;
};

/**
 * Pola tabel standar aplikasi (AGENTS.md 8.4). Tata letak dan jarak diatur
 * sepenuhnya di sini; halaman hanya mengirim konfigurasi.
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
    emptyState,
}: Props<T>) {
    const rows = data.data;
    const isEmpty = !table.loading && rows.length === 0;

    // Sel terakhir (kolom aksi, atau kolom data terakhir bila tanpa aksi)
    // mendapat jarak tepi kanan yang sama dengan header dan footer.
    const lastColumnIndex = columns.length - 1;
    const columnClass = (index: number, className?: string) =>
        cn(className, !rowActions && index === lastColumnIndex && EDGE_RIGHT);
    const actionClass = cn('w-px text-right', EDGE_RIGHT);

    return (
        <Card className="@container/table min-w-0 gap-0 py-0">
            <DataTableHeader
                title={title}
                description={description}
                table={table}
                searchPlaceholder={searchPlaceholder}
                filterGroups={filterGroups}
            />

            <div aria-busy={table.loading}>
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
                                        className={columnClass(
                                            index,
                                            column.className,
                                        )}
                                    >
                                        {column.header}
                                    </TableHead>
                                ))}
                                {rowActions && (
                                    <TableHead className={actionClass}>
                                        <span className="sr-only">Aksi</span>
                                    </TableHead>
                                )}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {table.loading
                                ? Array.from(
                                      {
                                          length: Math.max(
                                              rows.length,
                                              SKELETON_MIN_ROWS,
                                          ),
                                      },
                                      (_, rowIndex) => (
                                          <TableRow
                                              key={`skeleton-${rowIndex}`}
                                          >
                                              <TableCell
                                                  className={NUMBER_COLUMN}
                                              >
                                                  <Skeleton className="ml-auto h-4 w-4" />
                                              </TableCell>
                                              {columns.map((column, index) => (
                                                  <TableCell
                                                      key={column.id}
                                                      className={columnClass(
                                                          index,
                                                      )}
                                                  >
                                                      <Skeleton className="h-4 w-full max-w-40" />
                                                  </TableCell>
                                              ))}
                                              {rowActions && (
                                                  <TableCell
                                                      className={actionClass}
                                                  >
                                                      <Skeleton className="ml-auto size-7" />
                                                  </TableCell>
                                              )}
                                          </TableRow>
                                      ),
                                  )
                                : rows.map((row, rowIndex) => (
                                      <TableRow key={rowKey(row)}>
                                          <TableCell
                                              className={cn(
                                                  NUMBER_COLUMN,
                                                  'text-muted-foreground tabular-nums',
                                              )}
                                          >
                                              {rowNumber(data.meta, rowIndex)}
                                          </TableCell>
                                          {columns.map((column, index) => (
                                              <TableCell
                                                  key={column.id}
                                                  className={columnClass(
                                                      index,
                                                      column.className,
                                                  )}
                                              >
                                                  {column.cell(row)}
                                              </TableCell>
                                          ))}
                                          {rowActions && (
                                              <TableCell
                                                  className={actionClass}
                                              >
                                                  {rowActions(row)}
                                              </TableCell>
                                          )}
                                      </TableRow>
                                  ))}
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

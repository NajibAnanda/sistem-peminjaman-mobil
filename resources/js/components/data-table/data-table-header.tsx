import { Search } from 'lucide-react';
import { DataTableFilter } from '@/components/data-table/data-table-filter';
import { EDGE_X } from '@/components/data-table/layout';
import type { DataTableFilterGroup } from '@/components/data-table/types';
import { Input } from '@/components/ui/input';
import type { UseTableQueryReturn } from '@/hooks/use-table-query';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    table: UseTableQueryReturn;
    searchPlaceholder: string;
    filterGroups?: DataTableFilterGroup[];
};

/**
 * Header card tabel: judul (kiri) dan pencarian + Filter (kanan). Bila card
 * sempit, pencarian/filter turun ke bawah judul dan melebar penuh. Memakai
 * container query (lebar card), bukan lebar layar, karena sidebar ikut
 * memakan ruang.
 */
export function DataTableHeader({
    title,
    description,
    table,
    searchPlaceholder,
    filterGroups = [],
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col gap-3 border-b border-border py-4 @2xl/table:flex-row @2xl/table:items-center @2xl/table:justify-between',
                EDGE_X,
            )}
        >
            <div className="min-w-0">
                <h2 className="text-base font-semibold">{title}</h2>
                {description && (
                    <p className="truncate text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>

            <div className="flex w-full items-center gap-2 @2xl/table:w-auto">
                <div className="relative flex-1 @2xl/table:w-64 @2xl/table:flex-none">
                    <Search
                        className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        type="search"
                        value={table.search}
                        onChange={(event) =>
                            table.setSearch(event.target.value)
                        }
                        placeholder={searchPlaceholder}
                        aria-label={searchPlaceholder}
                        className="pl-8"
                        data-test="table-search"
                    />
                </div>
                {filterGroups.length > 0 && (
                    <DataTableFilter table={table} groups={filterGroups} />
                )}
            </div>
        </div>
    );
}

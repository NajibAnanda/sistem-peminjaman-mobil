import { ListFilter, RotateCcw } from 'lucide-react';
import { Fragment } from 'react';
import type { DataTableFilterGroup } from '@/components/data-table/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { UseTableQueryReturn } from '@/hooks/use-table-query';

type Props = {
    table: UseTableQueryReturn;
    groups: DataTableFilterGroup[];
};

export function DataTableFilter({ table, groups }: Props) {
    const count = table.activeFilterCount;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" data-test="table-filter-button">
                    <ListFilter aria-hidden="true" />
                    Filter
                    {count > 0 && (
                        <Badge
                            variant="secondary"
                            className="tabular-nums"
                            aria-label={`${count} filter aktif`}
                        >
                            {count}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                {groups.map((group, index) => (
                    <Fragment key={group.key}>
                        {index > 0 && <DropdownMenuSeparator />}
                        <DropdownMenuLabel>{group.label}</DropdownMenuLabel>
                        {group.options.map((option) => (
                            <DropdownMenuCheckboxItem
                                key={option.value}
                                checked={(
                                    table.filters[group.key] ?? []
                                ).includes(option.value)}
                                onCheckedChange={() =>
                                    table.toggleFilter(group.key, option.value)
                                }
                                // Dropdown tetap terbuka agar beberapa opsi bisa dicentang.
                                onSelect={(event) => event.preventDefault()}
                            >
                                {option.label}
                            </DropdownMenuCheckboxItem>
                        ))}
                    </Fragment>
                ))}
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    disabled={count === 0}
                    onSelect={() => table.resetFilters()}
                >
                    <RotateCcw aria-hidden="true" />
                    Reset filter
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

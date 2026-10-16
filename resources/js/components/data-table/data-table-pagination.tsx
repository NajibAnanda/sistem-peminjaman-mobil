import type { LucideIcon } from 'lucide-react';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { EDGE_X } from '@/components/data-table/layout';
import { pageWindow } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type Props = {
    meta: Paginated<unknown>['meta'];
    onPageChange: (page: number) => void;
    disabled?: boolean;
};

export function DataTablePagination({
    meta,
    onPageChange,
    disabled = false,
}: Props) {
    const current = meta.current_page;
    const last = Math.max(1, meta.last_page);
    // Bila hanya satu halaman, navigasi tetap tampil tetapi nonaktif.
    const locked = disabled || last <= 1;

    return (
        <div
            className={cn(
                'flex flex-col items-start gap-3 border-t border-border py-3 @xl/table:flex-row @xl/table:items-center @xl/table:justify-between',
                EDGE_X,
            )}
        >
            <p
                className="text-sm text-muted-foreground"
                data-test="table-summary"
            >
                {meta.total === 0
                    ? '0 data'
                    : `Menampilkan ${meta.from}–${meta.to} dari ${meta.total} data`}
            </p>

            <nav
                aria-label="Navigasi halaman"
                className="flex items-center gap-1"
            >
                <EdgeButton
                    icon={ChevronsLeft}
                    label="Halaman pertama"
                    disabled={locked || current <= 1}
                    onClick={() => onPageChange(1)}
                />
                <EdgeButton
                    icon={ChevronLeft}
                    label="Halaman sebelumnya"
                    disabled={locked || current <= 1}
                    onClick={() => onPageChange(current - 1)}
                />

                {pageWindow(current, last).map((page) => (
                    <Button
                        key={page}
                        variant={page === current ? 'default' : 'ghost'}
                        size="icon-sm"
                        className="tabular-nums"
                        aria-label={`Halaman ${page}`}
                        aria-current={page === current ? 'page' : undefined}
                        disabled={locked}
                        onClick={() => page !== current && onPageChange(page)}
                    >
                        {page}
                    </Button>
                ))}

                <EdgeButton
                    icon={ChevronRight}
                    label="Halaman berikutnya"
                    disabled={locked || current >= last}
                    onClick={() => onPageChange(current + 1)}
                />
                <EdgeButton
                    icon={ChevronsRight}
                    label="Halaman terakhir"
                    disabled={locked || current >= last}
                    onClick={() => onPageChange(last)}
                />
            </nav>
        </div>
    );
}

function EdgeButton({
    icon: Icon,
    label,
    disabled,
    onClick,
}: {
    icon: LucideIcon;
    label: string;
    disabled: boolean;
    onClick: () => void;
}) {
    return (
        <Tooltip>
            {/* Dibungkus span agar tooltip tetap muncul saat tombol nonaktif. */}
            <TooltipTrigger asChild>
                <span className="inline-flex">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={label}
                        disabled={disabled}
                        onClick={onClick}
                    >
                        <Icon aria-hidden="true" />
                    </Button>
                </span>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

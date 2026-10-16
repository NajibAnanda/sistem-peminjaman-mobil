import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/staff';
import type { Option, StaffFilters as Filters, StaffRole } from '@/types';

// Radix Select tidak menerima nilai kosong, jadi "semua" memakai nilai khusus.
const ALL = 'all';
const SEARCH_DELAY_MS = 300;

type Props = {
    filters: Filters;
    roles: Option<StaffRole>[];
};

function applyFilters(filters: Filters): void {
    const query = Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value),
    );

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

export function hasActiveFilters(filters: Filters): boolean {
    return Boolean(filters.search || filters.role || filters.status);
}

export function StaffFilters({ filters, roles }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(
            () => applyFilters({ ...filters, search: search || null }),
            SEARCH_DELAY_MS,
        );

        return () => clearTimeout(timeout);
    }, [search, filters]);

    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div className="relative flex-1 sm:max-w-xs">
                <Search
                    className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Cari nama atau email"
                    aria-label="Cari nama atau email"
                    className="pl-8"
                />
            </div>

            <Select
                value={filters.role ?? ALL}
                onValueChange={(value) =>
                    applyFilters({
                        ...filters,
                        role: value === ALL ? null : (value as StaffRole),
                    })
                }
            >
                <SelectTrigger
                    className="w-full sm:w-40"
                    aria-label="Filter peran"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>Semua peran</SelectItem>
                    {roles.map((role) => (
                        <SelectItem key={role.value} value={role.value}>
                            {role.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select
                value={filters.status ?? ALL}
                onValueChange={(value) =>
                    applyFilters({
                        ...filters,
                        status:
                            value === ALL ? null : (value as Filters['status']),
                    })
                }
            >
                <SelectTrigger
                    className="w-full sm:w-40"
                    aria-label="Filter status"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>Semua status</SelectItem>
                    <SelectItem value="active">Aktif</SelectItem>
                    <SelectItem value="inactive">Nonaktif</SelectItem>
                </SelectContent>
            </Select>

            {hasActiveFilters(filters) && (
                <Button
                    variant="ghost"
                    onClick={() => {
                        setSearch('');
                        applyFilters({
                            search: null,
                            role: null,
                            status: null,
                        });
                    }}
                >
                    <X aria-hidden="true" />
                    Hapus filter
                </Button>
            )}
        </div>
    );
}

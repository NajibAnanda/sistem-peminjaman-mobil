import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { TableQueryState } from '@/types';

const SEARCH_DEBOUNCE_MS = 300;

type Filters = Record<string, string[]>;

type Options = {
    /** URL halaman daftar, mis. index.url() dari Wayfinder. */
    url: string;
    /** Pencarian & filter yang sedang berlaku menurut server. */
    query: TableQueryState;
    /** Prop yang dimuat ulang saat tabel berubah (partial reload). */
    only: string[];
};

export type UseTableQueryReturn = {
    search: string;
    setSearch: (value: string) => void;
    filters: Filters;
    toggleFilter: (key: string, value: string) => void;
    resetFilters: () => void;
    resetAll: () => void;
    goToPage: (page: number) => void;
    activeFilterCount: number;
    /** Hasil saat ini dibatasi pencarian/filter (menurut server). */
    isFiltered: boolean;
    loading: boolean;
};

function buildParams(
    search: string,
    filters: Filters,
    page?: number,
): Record<string, string | string[] | number> {
    const params: Record<string, string | string[] | number> = {};

    if (search.trim() !== '') {
        params.search = search.trim();
    }

    for (const [key, values] of Object.entries(filters)) {
        if (values.length > 0) {
            params[key] = values;
        }
    }

    if (page !== undefined && page > 1) {
        params.page = page;
    }

    return params;
}

function countFilters(filters: Filters): number {
    return Object.values(filters).reduce(
        (sum, values) => sum + values.length,
        0,
    );
}

/**
 * Menyelaraskan pencarian, filter, dan halaman tabel dengan query string
 * (?search=&role[]=&page=). Semua pemrosesan terjadi di server; setiap
 * perubahan pencarian/filter kembali ke halaman 1.
 */
export function useTableQuery({
    url,
    query,
    only,
}: Options): UseTableQueryReturn {
    const [search, setSearch] = useState(query.search ?? '');
    const [filters, setFilters] = useState<Filters>(query.filters);
    const [loading, setLoading] = useState(false);

    // Server adalah sumber kebenaran: bila query dari server berubah (mis.
    // tombol back browser), centang filter lokal ikut disesuaikan.
    const serverFiltersKey = JSON.stringify(query.filters);
    const [syncedFiltersKey, setSyncedFiltersKey] = useState(serverFiltersKey);

    if (syncedFiltersKey !== serverFiltersKey) {
        setSyncedFiltersKey(serverFiltersKey);
        setFilters(query.filters);
    }

    const onlyKey = only.join(',');

    const visit = useCallback(
        (nextSearch: string, nextFilters: Filters, page?: number) => {
            router.get(url, buildParams(nextSearch, nextFilters, page), {
                preserveState: true,
                preserveScroll: true,
                // Pencarian/filter mengganti entri riwayat; pindah halaman tidak,
                // agar tombol back browser kembali ke halaman sebelumnya.
                replace: page === undefined,
                only: onlyKey.split(','),
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            });
        },
        [url, onlyKey],
    );

    const serverSearch = query.search ?? '';

    useEffect(() => {
        if (search.trim() === serverSearch) {
            return;
        }

        const timeout = setTimeout(
            () => visit(search, filters),
            SEARCH_DEBOUNCE_MS,
        );

        return () => clearTimeout(timeout);
    }, [search, serverSearch, filters, visit]);

    const toggleFilter = (key: string, value: string) => {
        const current = filters[key] ?? [];
        const next = {
            ...filters,
            [key]: current.includes(value)
                ? current.filter((item) => item !== value)
                : [...current, value],
        };

        setFilters(next);
        visit(search, next);
    };

    const emptyFilters = (): Filters =>
        Object.fromEntries(Object.keys(filters).map((key) => [key, []]));

    const resetFilters = () => {
        const next = emptyFilters();

        setFilters(next);
        visit(search, next);
    };

    const resetAll = () => {
        const next = emptyFilters();

        setSearch('');
        setFilters(next);
        visit('', next);
    };

    const goToPage = (page: number) => visit(serverSearch, query.filters, page);

    return {
        search,
        setSearch,
        filters,
        toggleFilter,
        resetFilters,
        resetAll,
        goToPage,
        activeFilterCount: countFilters(filters),
        isFiltered: serverSearch !== '' || countFilters(query.filters) > 0,
        loading,
    };
}

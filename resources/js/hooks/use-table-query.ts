import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { TableQueryState } from '@/types';

const SEARCH_DEBOUNCE_MS = 300;

type Filters = Record<string, string[]>;

/** Rentang tanggal (Y-m-d); string kosong = tidak dibatasi. */
export type TablePeriod = { from: string; to: string };

const EMPTY_PERIOD: TablePeriod = { from: '', to: '' };

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
    /** Tabel ini memakai filter periode (server mengirim `query.period`). */
    hasPeriod: boolean;
    /** Periode wajib: tidak bisa dikosongkan, reset kembali ke periode bawaan. */
    periodRequired: boolean;
    period: TablePeriod;
    setPeriod: (period: TablePeriod) => void;
    /** Hasil saat ini dibatasi pencarian/filter/periode (menurut server). */
    isFiltered: boolean;
    loading: boolean;
};

function serverPeriod(query: TableQueryState): TablePeriod {
    return {
        from: query.period?.from ?? '',
        to: query.period?.to ?? '',
    };
}

function defaultPeriod(query: TableQueryState): TablePeriod {
    return query.period?.default ?? EMPTY_PERIOD;
}

function samePeriod(a: TablePeriod, b: TablePeriod): boolean {
    return a.from === b.from && a.to === b.to;
}

function buildParams(
    search: string,
    filters: Filters,
    period: TablePeriod,
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

    if (period.from !== '') {
        params.from = period.from;
    }

    if (period.to !== '') {
        params.to = period.to;
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
 * Menyelaraskan pencarian, filter, periode, dan halaman tabel dengan query
 * string (?search=&role[]=&from=&to=&page=). Semua pemrosesan terjadi di
 * server; setiap perubahan pencarian/filter/periode kembali ke halaman 1.
 */
export function useTableQuery({
    url,
    query,
    only,
}: Options): UseTableQueryReturn {
    const [search, setSearch] = useState(query.search ?? '');
    const [filters, setFilters] = useState<Filters>(query.filters);
    const [period, setPeriodState] = useState<TablePeriod>(serverPeriod(query));
    const [loading, setLoading] = useState(false);

    // Server adalah sumber kebenaran: bila query dari server berubah (mis.
    // tombol back browser), centang filter dan periode lokal ikut disesuaikan.
    const serverStateKey = JSON.stringify([query.filters, query.period]);
    const [syncedStateKey, setSyncedStateKey] = useState(serverStateKey);

    if (syncedStateKey !== serverStateKey) {
        setSyncedStateKey(serverStateKey);
        setFilters(query.filters);
        setPeriodState(serverPeriod(query));
    }

    const onlyKey = only.join(',');

    const visit = useCallback(
        (
            nextSearch: string,
            nextFilters: Filters,
            nextPeriod: TablePeriod,
            page?: number,
        ) => {
            router.get(
                url,
                buildParams(nextSearch, nextFilters, nextPeriod, page),
                {
                    preserveState: true,
                    preserveScroll: true,
                    // Pencarian/filter mengganti entri riwayat; pindah halaman tidak,
                    // agar tombol back browser kembali ke halaman sebelumnya.
                    replace: page === undefined,
                    only: onlyKey.split(','),
                    onStart: () => setLoading(true),
                    onFinish: () => setLoading(false),
                },
            );
        },
        [url, onlyKey],
    );

    const serverSearch = query.search ?? '';

    useEffect(() => {
        if (search.trim() === serverSearch) {
            return;
        }

        const timeout = setTimeout(
            () => visit(search, filters, period),
            SEARCH_DEBOUNCE_MS,
        );

        return () => clearTimeout(timeout);
    }, [search, serverSearch, filters, period, visit]);

    const toggleFilter = (key: string, value: string) => {
        const current = filters[key] ?? [];
        const next = {
            ...filters,
            [key]: current.includes(value)
                ? current.filter((item) => item !== value)
                : [...current, value],
        };

        setFilters(next);
        visit(search, next, period);
    };

    const emptyFilters = (): Filters =>
        Object.fromEntries(Object.keys(filters).map((key) => [key, []]));

    const resetFilters = () => {
        const next = emptyFilters();

        setFilters(next);
        visit(search, next, period);
    };

    const setPeriod = (next: TablePeriod) => {
        setPeriodState(next);
        visit(search, filters, next);
    };

    const resetAll = () => {
        const next = emptyFilters();
        const initialPeriod = defaultPeriod(query);

        setSearch('');
        setFilters(next);
        setPeriodState(initialPeriod);
        visit('', next, initialPeriod);
    };

    const goToPage = (page: number) =>
        visit(serverSearch, query.filters, serverPeriod(query), page);

    const activePeriod = serverPeriod(query);

    return {
        search,
        setSearch,
        filters,
        toggleFilter,
        resetFilters,
        resetAll,
        goToPage,
        activeFilterCount: countFilters(filters),
        hasPeriod: query.period !== undefined,
        periodRequired: query.period?.default !== undefined,
        period,
        setPeriod,
        isFiltered:
            serverSearch !== '' ||
            countFilters(query.filters) > 0 ||
            // Periode bawaan (bulan berjalan) tidak dihitung sebagai filter.
            !samePeriod(activePeriod, defaultPeriod(query)),
        loading,
    };
}

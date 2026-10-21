import { useEffect, useState } from 'react';
import { search } from '@/routes';
import type { GlobalSearchGroup } from '@/types';

const DEBOUNCE_MS = 300;

/** Sama dengan GlobalSearchRequest::MIN_LENGTH di server. */
export const GLOBAL_SEARCH_MIN_LENGTH = 2;

type State = {
    /** Kata kunci yang hasilnya sedang ditampilkan. */
    term: string;
    groups: GlobalSearchGroup[];
    failed: boolean;
    /** Kata kunci yang request-nya sedang berjalan (setelah debounce). */
    fetching: string | null;
};

export type UseGlobalSearchReturn = {
    /** Hasil kata kunci terakhir; tetap ada selama kata kunci baru dimuat. */
    groups: GlobalSearchGroup[];
    /** Hasil untuk kata kunci saat ini belum ada (menunggu atau memuat). */
    loading: boolean;
    /** Request ke server sedang berjalan (bukan sekadar menunggu debounce). */
    fetching: boolean;
    failed: boolean;
    /** Kata kunci sudah cukup panjang untuk dicari. */
    ready: boolean;
};

/**
 * Hasil pencarian global dari server, di-debounce dan membatalkan request
 * lama saat kata kunci berubah agar hasil lama tidak menimpa yang baru.
 */
export function useGlobalSearch(query: string): UseGlobalSearchReturn {
    const term = query.trim();
    const ready = term.length >= GLOBAL_SEARCH_MIN_LENGTH;
    const [state, setState] = useState<State>({
        term: '',
        groups: [],
        failed: false,
        fetching: null,
    });

    useEffect(() => {
        if (!ready) {
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(async () => {
            setState((previous) => ({ ...previous, fetching: term }));

            try {
                const response = await fetch(
                    search.url({ query: { q: term } }),
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(String(response.status));
                }

                const data = (await response.json()) as {
                    groups: GlobalSearchGroup[];
                };
                setState({
                    term,
                    groups: data.groups,
                    failed: false,
                    fetching: null,
                });
            } catch {
                if (!controller.signal.aborted) {
                    setState({
                        term,
                        groups: [],
                        failed: true,
                        fetching: null,
                    });
                }
            }
        }, DEBOUNCE_MS);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [term, ready]);

    const current = ready && state.term === term;

    return {
        groups: ready ? state.groups : [],
        loading: ready && !current,
        fetching: ready && !current && state.fetching === term,
        failed: current && state.failed,
        ready,
    };
}

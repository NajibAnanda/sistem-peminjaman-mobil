import { router } from '@inertiajs/react';
import { Command as CommandPrimitive } from 'cmdk';
import type { LucideIcon } from 'lucide-react';
import { CarFront, ClipboardList, Search, UserCog, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { SearchResultSkeleton } from '@/components/skeletons/search-result-skeleton';
import { Button } from '@/components/ui/button';
import {
    CommandGroup,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    GLOBAL_SEARCH_MIN_LENGTH,
    useGlobalSearch,
} from '@/hooks/use-global-search';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import { cn } from '@/lib/utils';

/** Ikon per kategori (App\Support\Search\*::key()); kategori baru cukup ditambah di sini. */
const CATEGORY_ICONS: Record<string, LucideIcon> = {
    bookings: ClipboardList,
    cars: CarFront,
    employees: Users,
    staff: UserCog,
};

const SHORTCUT_KEY = 'k';

function StatusMessage({ children }: { children: ReactNode }) {
    return (
        <div
            role="status"
            aria-live="polite"
            className="flex items-center justify-center gap-2 px-3 py-6 text-center text-sm text-muted-foreground"
        >
            {children}
        </div>
    );
}

/**
 * Pencarian global di top bar: ketik langsung di kolom, hasil muncul sebagai
 * dropdown di bawahnya (navigasi panah/Enter/Esc lewat cmdk). Ctrl+K
 * memfokuskan kolom. Di layar sempit tampil sebagai tombol ikon yang
 * membentangkan kolom di atas top bar. Hasil dari server, sudah disaring
 * sesuai hak akses petugas.
 */
export function GlobalSearch() {
    const input = useRef<HTMLInputElement>(null);
    const [query, setQuery] = useState('');
    const [focused, setFocused] = useState(false);
    // Layar sempit: kolom disembunyikan sampai tombol ikon diklik.
    const [expanded, setExpanded] = useState(false);
    const { groups, loading, fetching, failed, ready } = useGlobalSearch(
        focused ? query : '',
    );
    const open = focused && query.trim() !== '';
    const showSkeleton = useDelayedFlag(fetching);

    const focusInput = () => {
        setExpanded(true);
        // Tunggu kolom tampil (layar sempit) sebelum difokuskan.
        requestAnimationFrame(() => input.current?.focus());
    };

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (
                (event.ctrlKey || event.metaKey) &&
                event.key.toLowerCase() === SHORTCUT_KEY
            ) {
                event.preventDefault();
                focusInput();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const close = () => {
        setQuery('');
        setExpanded(false);
        input.current?.blur();
    };

    const visit = (url: string) => {
        close();
        router.visit(url);
    };

    return (
        <>
            <div
                className={cn(
                    'md:relative md:block md:w-64 lg:w-80',
                    expanded
                        ? 'absolute inset-x-3 top-1/2 z-10 -translate-y-1/2 md:static md:translate-y-0'
                        : 'hidden',
                )}
            >
                {/* Penyaringan dilakukan server; cmdk tidak boleh menyaring ulang. */}
                <CommandPrimitive
                    shouldFilter={false}
                    loop
                    label="Pencarian"
                    className="relative"
                    onKeyDown={(event) => {
                        if (event.key === 'Escape') {
                            close();
                        }
                    }}
                >
                    <div className="relative">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <CommandPrimitive.Input
                            ref={input}
                            value={query}
                            onValueChange={setQuery}
                            onFocus={() => setFocused(true)}
                            onBlur={() => {
                                setFocused(false);
                                setExpanded(false);
                            }}
                            placeholder="Cari…"
                            aria-label="Cari peminjaman, mobil, pegawai, atau petugas"
                            className="h-9 w-full min-w-0 rounded-lg border border-input bg-background py-1 pr-2.5 pl-8 text-sm transition-colors outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
                            data-test="global-search-input"
                        />
                    </div>

                    {open && (
                        <div
                            // Klik di dropdown tidak boleh memindahkan fokus dari
                            // kolom (blur menutup dropdown sebelum item terpilih).
                            onMouseDown={(event) => event.preventDefault()}
                            className="absolute top-full right-0 left-0 z-50 mt-2 overflow-hidden rounded-xl bg-popover text-popover-foreground shadow-md ring-1 ring-foreground/10 md:left-auto md:w-104"
                            data-test="global-search-results"
                        >
                            <CommandList
                                className="max-h-[min(24rem,60svh)] p-1"
                                aria-busy={loading}
                            >
                                {!ready ? (
                                    <StatusMessage>
                                        Ketik minimal {GLOBAL_SEARCH_MIN_LENGTH}{' '}
                                        huruf untuk mencari.
                                    </StatusMessage>
                                ) : showSkeleton ? (
                                    <SearchResultSkeleton heading />
                                ) : loading && groups.length === 0 ? (
                                    // Sebelum skeleton tampil: ruang disiapkan
                                    // tanpa isi agar tidak berkedip.
                                    <div className="invisible">
                                        <SearchResultSkeleton heading />
                                    </div>
                                ) : failed ? (
                                    <StatusMessage>
                                        Pencarian gagal. Coba lagi.
                                    </StatusMessage>
                                ) : groups.length === 0 ? (
                                    <StatusMessage>
                                        Tidak ada hasil.
                                    </StatusMessage>
                                ) : (
                                    groups.map((group) => {
                                        const Icon =
                                            CATEGORY_ICONS[group.key] ?? Search;

                                        return (
                                            <CommandGroup
                                                key={group.key}
                                                heading={group.label}
                                            >
                                                {group.items.map((item) => (
                                                    <CommandItem
                                                        key={`${group.key}-${item.id}`}
                                                        value={`${group.key}-${item.id}`}
                                                        onSelect={() =>
                                                            visit(item.url)
                                                        }
                                                    >
                                                        <Icon
                                                            className="text-muted-foreground"
                                                            aria-hidden="true"
                                                        />
                                                        <span className="flex min-w-0 flex-col">
                                                            <span className="truncate">
                                                                {item.title}
                                                            </span>
                                                            <span className="truncate text-xs text-muted-foreground">
                                                                {item.subtitle}
                                                            </span>
                                                        </span>
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        );
                                    })
                                )}
                            </CommandList>
                        </div>
                    )}
                </CommandPrimitive>
            </div>

            <Button
                variant="ghost"
                size="icon"
                onClick={focusInput}
                className={cn('md:hidden', expanded && 'invisible')}
                aria-label="Cari"
            >
                <Search aria-hidden="true" />
            </Button>
        </>
    );
}

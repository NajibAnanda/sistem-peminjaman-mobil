import { AppearanceToggle } from '@/components/appearance-toggle';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { GlobalSearch } from '@/components/global-search';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * Top bar: [toggle sidebar][breadcrumb] ...... [pencarian global][tema].
 * Berada di luar area scroll konten (lihat AppSidebarLayout), sehingga tidak
 * pernah bergerak; latar solid agar isi halaman tidak terlihat di belakangnya.
 * Tingginya sengaja tetap saat sidebar diciutkan agar garis bawahnya tidak
 * melompat.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="relative z-30 flex h-16 shrink-0 items-center gap-2 border-b border-border bg-background px-4">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1 shrink-0" />
                <div className="min-w-0 truncate">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>
            <div className="flex shrink-0 items-center gap-1">
                <GlobalSearch />
                <AppearanceToggle />
            </div>
        </header>
    );
}

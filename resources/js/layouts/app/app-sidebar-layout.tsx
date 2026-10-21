import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

/**
 * Area konten setinggi layar: top bar diam di atas, hanya isi halaman di
 * bawahnya yang di-scroll. (Scroll di level window membuat top bar ikut
 * bergeser karena bingkai konten punya margin 0,5rem di layar md ke atas.)
 * `scroll-region` membuat Inertia mengingat/mereset posisi scroll kontainer
 * ini seperti scroll window. `scrollbar-gutter: stable` menyisakan ruang
 * scrollbar sejak awal agar isi tidak bergeser saat scrollbar muncul.
 */
export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="h-svh min-w-0 overflow-hidden md:h-[calc(100svh-1rem)]"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div
                    scroll-region=""
                    className="flex min-h-0 flex-1 [scrollbar-gutter:stable] flex-col overflow-x-clip overflow-y-auto"
                    data-test="content-scroll"
                >
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}

import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: edit(),
        icon: null,
    },
    {
        title: 'Keamanan',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Tampilan',
        href: editAppearance(),
        icon: null,
    },
];

/**
 * Halaman pengaturan akun: menu di kiri (di atas pada layar sempit) dan card
 * form selebar sisa area konten, sama seperti halaman lain.
 */
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Pengaturan"
                description="Kelola profil, keamanan login, dan tampilan aplikasi untuk akun Anda."
            />

            <div className="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:gap-6">
                <nav
                    className="flex gap-1 lg:w-48 lg:shrink-0 lg:flex-col"
                    aria-label="Pengaturan"
                >
                    {sidebarNavItems.map((item, index) => (
                        <Button
                            key={`${toUrl(item.href)}-${index}`}
                            size="sm"
                            variant="ghost"
                            asChild
                            className={cn('justify-start lg:w-full', {
                                'bg-muted': isCurrentOrParentUrl(item.href),
                            })}
                        >
                            <Link
                                href={item.href}
                                aria-current={
                                    isCurrentOrParentUrl(item.href)
                                        ? 'page'
                                        : undefined
                                }
                            >
                                {item.title}
                            </Link>
                        </Button>
                    ))}
                </nav>

                <div className="flex min-w-0 flex-1 flex-col gap-4">
                    {children}
                </div>
            </div>
        </div>
    );
}

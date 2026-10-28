import { router, usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { logout } from '@/routes';

export function NavUser() {
    const { auth } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const [confirmLogoutOpen, setConfirmLogoutOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    if (!auth.user) {
        return null;
    }

    const handleConfirmLogout = () => {
        router.post(
            logout.url(),
            {},
            {
                onStart: () => setProcessing(true),
                onError: () => {
                    window.location.href = '/login';
                },
                onFinish: () => {
                    setProcessing(false);
                    setConfirmLogoutOpen(false);
                    router.flushAll();
                },
            },
        );
    };

    return (
        <>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton
                                size="lg"
                                className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                                data-test="sidebar-menu-button"
                            >
                                <UserInfo user={auth.user} />
                                <ChevronsUpDown className="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                            align="end"
                            side={
                                isMobile
                                    ? 'bottom'
                                    : state === 'collapsed'
                                      ? 'left'
                                      : 'bottom'
                            }
                        >
                            <UserMenuContent
                                user={auth.user}
                                onLogout={() => setConfirmLogoutOpen(true)}
                            />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>

            <ConfirmDialog
                open={confirmLogoutOpen}
                onOpenChange={setConfirmLogoutOpen}
                title="Keluar dari sistem?"
                description="Sesi Anda saat ini akan diakhiri. Anda perlu memasukkan email dan password kembali untuk mengakses sistem."
                confirmLabel="Keluar"
                cancelLabel="Batal"
                destructive
                processing={processing}
                onConfirm={handleConfirmLogout}
            />
        </>
    );
}

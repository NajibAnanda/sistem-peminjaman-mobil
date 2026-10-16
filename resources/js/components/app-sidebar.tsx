import { Link, usePage, usePoll } from '@inertiajs/react';
import {
    CalendarRange,
    CarFront,
    ClipboardList,
    History,
    LayoutGrid,
    ScrollText,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import {
    BookingAlertBadge,
    bookingAlertTooltip,
} from '@/components/booking-alert-badge';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as activityLog } from '@/routes/activity-log';
import { index as bookings } from '@/routes/bookings';
import { index as cars } from '@/routes/cars';
import { index as employees } from '@/routes/employees';
import { index as history } from '@/routes/history';
import { index as schedule } from '@/routes/schedule';
import { index as staff } from '@/routes/staff';
import type { NavItem } from '@/types';

const SECOND_MS = 1000;

export function AppSidebar() {
    const { auth, alerts, pollingIntervalSeconds } = usePage().props;

    // Badge Terlambat/Belum Diambil diperbarui tanpa memuat ulang halaman;
    // hanya prop `alerts` yang diminta ke server (prd.md 7.3).
    usePoll(pollingIntervalSeconds * SECOND_MS, { only: ['alerts'] });

    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'Jadwal Armada', href: schedule(), icon: CalendarRange },
        {
            title: 'Peminjaman',
            href: bookings(),
            icon: ClipboardList,
            badge: <BookingAlertBadge alerts={alerts} />,
            tooltip: bookingAlertTooltip(alerts),
        },
        { title: 'Riwayat', href: history(), icon: History },
        { title: 'Data Mobil', href: cars(), icon: CarFront },
        { title: 'Data Karyawan', href: employees(), icon: Users },
    ];

    // Menyembunyikan menu hanya untuk kenyamanan; hak akses tetap dicek di server.
    const managerNavItems: NavItem[] = [
        ...(auth.can.manageStaff
            ? [{ title: 'Kelola Petugas', href: staff(), icon: UserCog }]
            : []),
        ...(auth.can.viewActivityLog
            ? [
                  {
                      title: 'Log Aktivitas',
                      href: activityLog(),
                      icon: ScrollText,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain label="Menu" items={mainNavItems} />
                {managerNavItems.length > 0 && (
                    <NavMain label="Pengelola" items={managerNavItems} />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

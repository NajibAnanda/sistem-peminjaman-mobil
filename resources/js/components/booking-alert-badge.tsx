import { SidebarMenuBadge } from '@/components/ui/sidebar';
import type { BookingAlerts } from '@/types';

type Props = {
    alerts: BookingAlerts | null;
};

/**
 * Jumlah Terlambat (merah) dan Belum Diambil (kuning) di menu Peminjaman
 * (FR-21, FR-22). Teks untuk pembaca layar menjelaskan arti tiap angka
 * karena warna saja tidak cukup.
 */
export function BookingAlertBadge({ alerts }: Props) {
    if (!alerts || (alerts.overdue === 0 && alerts.not_picked_up === 0)) {
        return null;
    }

    return (
        <SidebarMenuBadge
            className="gap-1 px-0"
            data-test="booking-alert-badge"
        >
            {alerts.overdue > 0 && (
                <span className="rounded-md bg-destructive/10 px-1.5 text-destructive dark:bg-destructive/20">
                    {alerts.overdue}
                    <span className="sr-only"> Terlambat</span>
                </span>
            )}
            {alerts.not_picked_up > 0 && (
                <span className="rounded-md bg-warning/10 px-1.5 text-warning dark:bg-warning/20">
                    {alerts.not_picked_up}
                    <span className="sr-only"> Belum Diambil</span>
                </span>
            )}
        </SidebarMenuBadge>
    );
}

/** Teks tooltip menu Peminjaman saat sidebar diciutkan. */
export function bookingAlertTooltip(alerts: BookingAlerts | null): string {
    const parts = ['Peminjaman'];

    if (alerts && alerts.overdue > 0) {
        parts.push(`${alerts.overdue} Terlambat`);
    }

    if (alerts && alerts.not_picked_up > 0) {
        parts.push(`${alerts.not_picked_up} Belum Diambil`);
    }

    return parts.join(' · ');
}

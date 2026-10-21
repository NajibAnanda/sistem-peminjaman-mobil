import type { Auth } from '@/types/auth';
import type { BookingAlerts } from '@/types/booking';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            timezone: string;
            auth: Auth;
            sidebarOpen: boolean;
            /** Jumlah untuk badge menu; null bila belum login. */
            alerts: BookingAlerts | null;
            pollingIntervalSeconds: number;
            /** Kelipatan menit di pemilih waktu (config/fleet.php). */
            minuteStep: number;
            /** Batas form peminjaman (config/fleet.php), untuk petunjuk. */
            bookingRules: {
                purposeMinLength: number;
                reasonMinLength: number;
                minDurationMinutes: number;
                /** null = tanpa batas maksimal. */
                maxDurationDays: number | null;
                /** Di atas ini form meminta konfirmasi (tidak memblokir). */
                longBookingWarningDays: number;
            };
            [key: string]: unknown;
        };
    }
}

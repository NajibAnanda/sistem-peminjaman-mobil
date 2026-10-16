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
            [key: string]: unknown;
        };
    }
}

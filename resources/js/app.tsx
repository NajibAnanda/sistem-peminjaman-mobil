import { createInertiaApp } from '@inertiajs/react';
import { AppProviders } from '@/components/app-providers';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Peminjaman Mobil';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Halaman error berdiri sendiri: bisa tampil untuk tamu.
            case name === 'error':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return <AppProviders>{app}</AppProviders>;
    },
    progress: {
        color: 'var(--primary)',
    },
});

// Menerapkan mode terang/gelap saat halaman dimuat.
initializeTheme();

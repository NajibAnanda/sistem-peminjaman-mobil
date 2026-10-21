import type { ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useFlashToast } from '@/hooks/use-flash-toast';

/**
 * Provider global yang membungkus setiap halaman. Sengaja di file sendiri,
 * bukan di app.tsx: komponen di app.tsx membuat React Refresh mengimpor ulang
 * app.tsx dengan URL bertimestamp setelah ada perubahan saat dev server
 * berjalan, sehingga createInertiaApp berjalan dua kali (createRoot ganda,
 * halaman bisa kosong).
 */
export function AppProviders({ children }: { children: ReactNode }) {
    useFlashToast();

    return (
        <TooltipProvider delayDuration={0}>
            {children}
            <Toaster position="top-right" />
        </TooltipProvider>
    );
}

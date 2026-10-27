import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

const DEFAULT_TITLES: Record<FlashToast['type'], string> = {
    success: 'Berhasil',
    info: 'Informasi',
    warning: 'Peringatan',
    error: 'Terjadi Kesalahan',
};

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            const title = data.title || DEFAULT_TITLES[data.type];
            toast[data.type](title, {
                description: data.message,
            });
        });
    }, []);
}

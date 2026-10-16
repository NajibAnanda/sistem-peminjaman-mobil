import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Menangkap data flash Inertia dengan kunci tertentu. Data flash tidak
 * disimpan di riwayat browser, cocok untuk informasi sekali tampil seperti
 * password sementara.
 */
export function useFlashData<T>(key: string): [T | null, () => void] {
    const [data, setData] = useState<T | null>(null);

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash as
                | Record<string, unknown>
                | undefined;

            if (flash && key in flash) {
                setData(flash[key] as T);
            }
        });
    }, [key]);

    return [data, () => setData(null)];
}

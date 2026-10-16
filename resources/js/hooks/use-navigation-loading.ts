import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * true selama Inertia memuat halaman (mis. saat filter atau halaman daftar
 * berganti), untuk menampilkan loading state pada daftar.
 */
export function useNavigationLoading(): boolean {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setLoading(true));
        const removeFinish = router.on('finish', () => setLoading(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return loading;
}

import { useEffect, useState } from 'react';

/**
 * Jeda sebelum skeleton tampil. Pemuatan yang lebih cepat dari ini langsung
 * menampilkan data tanpa skeleton, agar layar tidak berkedip.
 */
export const SKELETON_DELAY_MS = 200;

/**
 * `true` hanya bila `active` sudah bertahan selama `delay` ms; langsung
 * `false` begitu `active` berakhir.
 */
export function useDelayedFlag(
    active: boolean,
    delay: number = SKELETON_DELAY_MS,
): boolean {
    const [elapsed, setElapsed] = useState(false);

    useEffect(() => {
        if (!active) {
            return;
        }

        const timeout = setTimeout(() => setElapsed(true), delay);

        return () => {
            clearTimeout(timeout);
            setElapsed(false);
        };
    }, [active, delay]);

    return active && elapsed;
}

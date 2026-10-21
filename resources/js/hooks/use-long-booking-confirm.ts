import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { minutesBetween } from '@/components/date-picker/utils';
import { formatMinutes } from '@/lib/datetime';

const MINUTES_PER_DAY = 1440;

export type LongBookingConfirm = {
    /**
     * Jalankan `proceed` langsung, atau minta konfirmasi dulu bila durasi
     * (nilai "2026-10-17T14:30") melebihi ambang di config.
     */
    confirmIfLong: (start: string, end: string, proceed: () => void) => void;
    /** Props untuk <ConfirmDialog>. */
    dialog: {
        open: boolean;
        onOpenChange: (open: boolean) => void;
        title: string;
        description: string;
        cancelLabel: string;
        confirmLabel: string;
        onConfirm: () => void;
    };
};

/**
 * Peringatan durasi peminjaman yang panjang (FLEET_LONG_BOOKING_WARNING_DAYS).
 * Hanya pengingat salah ketik tanggal: tidak memblokir penyimpanan, dan
 * batas sebenarnya (bila ada) tetap divalidasi server.
 */
export function useLongBookingConfirm(): LongBookingConfirm {
    const { longBookingWarningDays } = usePage().props.bookingRules;
    const [pending, setPending] = useState<{
        minutes: number;
        proceed: () => void;
    } | null>(null);

    const confirmIfLong = (start: string, end: string, proceed: () => void) => {
        const minutes = start && end ? minutesBetween(start, end) : 0;

        if (minutes > longBookingWarningDays * MINUTES_PER_DAY) {
            setPending({ minutes, proceed });

            return;
        }

        proceed();
    };

    return {
        confirmIfLong,
        dialog: {
            open: pending !== null,
            onOpenChange: (open) => !open && setPending(null),
            title: 'Periksa durasi peminjaman',
            description: pending
                ? `Durasi peminjaman ${formatMinutes(pending.minutes)}. Pastikan tanggal sudah benar.`
                : '',
            cancelLabel: 'Periksa lagi',
            confirmLabel: 'Ya, simpan',
            onConfirm: () => {
                const proceed = pending?.proceed;

                setPending(null);
                proceed?.();
            },
        },
    };
}

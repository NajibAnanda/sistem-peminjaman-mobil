import { useState } from 'react';
import type { Booking } from '@/types';

export type BookingDialogType = 'pickup' | 'return' | 'extend' | 'cancel';

export type BookingDialogState = {
    type: BookingDialogType;
    booking: Booking;
    /** Naik setiap kali dialog dibuka agar isi form selalu mulai dari awal. */
    key: number;
};

export type UseBookingDialogsReturn = {
    /**
     * Dialog terakhir yang dibuka. Tetap ada saat dialog menutup agar animasi
     * tutup tidak menampilkan dialog kosong; lihat `visible`.
     */
    current: BookingDialogState | null;
    visible: boolean;
    open: (type: BookingDialogType, booking: Booking) => void;
    setVisible: (visible: boolean) => void;
};

/**
 * Status dialog aksi peminjaman (serah terima, perpanjang, batal), dipakai
 * bersama halaman daftar dan detail.
 */
export function useBookingDialogs(): UseBookingDialogsReturn {
    const [current, setCurrent] = useState<BookingDialogState | null>(null);
    const [visible, setVisible] = useState(false);

    return {
        current,
        visible,
        open: (type, booking) => {
            setCurrent((previous) => ({
                type,
                booking,
                key: (previous?.key ?? 0) + 1,
            }));
            setVisible(true);
        },
        setVisible,
    };
}

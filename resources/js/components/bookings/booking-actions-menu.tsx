import { Ban, CalendarPlus, Eye, KeyRound, Pencil, Undo2 } from 'lucide-react';
import { RowActions } from '@/components/data-table/row-actions';
import type { BookingDialogType } from '@/hooks/use-booking-dialogs';
import { edit, show } from '@/routes/bookings';
import type { Booking } from '@/types';

type Props = {
    booking: Booking;
    onAction: (action: BookingDialogType, booking: Booking) => void;
};

/** Aksi baris daftar peminjaman, menyesuaikan status booking. */
export function BookingActionsMenu({ booking, onAction }: Props) {
    const { actions } = booking;

    return (
        <RowActions
            label={`Aksi untuk ${booking.code}`}
            actions={[
                { label: 'Lihat detail', icon: Eye, href: show(booking.id) },
                {
                    label: 'Serah terima ambil',
                    icon: KeyRound,
                    onSelect: () => onAction('pickup', booking),
                    hidden: !actions.pickup,
                },
                {
                    label: 'Serah terima kembali',
                    icon: Undo2,
                    onSelect: () => onAction('return', booking),
                    hidden: !actions.return,
                },
                {
                    label: 'Perpanjang',
                    icon: CalendarPlus,
                    onSelect: () => onAction('extend', booking),
                    hidden: !actions.extend,
                },
                {
                    label: 'Edit jadwal',
                    icon: Pencil,
                    href: edit(booking.id),
                    hidden: !actions.edit,
                },
                {
                    label: 'Batalkan',
                    icon: Ban,
                    onSelect: () => onAction('cancel', booking),
                    destructive: true,
                    hidden: !actions.cancel,
                },
            ]}
        />
    );
}

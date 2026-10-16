import { Link } from '@inertiajs/react';
import {
    Ban,
    CalendarPlus,
    Eye,
    KeyRound,
    MoreHorizontal,
    Pencil,
    Undo2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`Aksi untuk ${booking.code}`}
                >
                    <MoreHorizontal aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                    <Link href={show(booking.id)}>
                        <Eye aria-hidden="true" />
                        Lihat detail
                    </Link>
                </DropdownMenuItem>
                {actions.pickup && (
                    <DropdownMenuItem
                        onSelect={() => onAction('pickup', booking)}
                    >
                        <KeyRound aria-hidden="true" />
                        Serah terima ambil
                    </DropdownMenuItem>
                )}
                {actions.return && (
                    <DropdownMenuItem
                        onSelect={() => onAction('return', booking)}
                    >
                        <Undo2 aria-hidden="true" />
                        Serah terima kembali
                    </DropdownMenuItem>
                )}
                {actions.extend && (
                    <DropdownMenuItem
                        onSelect={() => onAction('extend', booking)}
                    >
                        <CalendarPlus aria-hidden="true" />
                        Perpanjang
                    </DropdownMenuItem>
                )}
                {actions.edit && (
                    <DropdownMenuItem asChild>
                        <Link href={edit(booking.id)}>
                            <Pencil aria-hidden="true" />
                            Edit jadwal
                        </Link>
                    </DropdownMenuItem>
                )}
                {actions.cancel && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => onAction('cancel', booking)}
                        >
                            <Ban aria-hidden="true" />
                            Batalkan
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

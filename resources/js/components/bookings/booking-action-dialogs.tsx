import { BookingCancelDialog } from '@/components/bookings/booking-cancel-dialog';
import { BookingExtendDialog } from '@/components/bookings/booking-extend-dialog';
import { BookingHandoverDialog } from '@/components/bookings/booking-handover-dialog';
import type { UseBookingDialogsReturn } from '@/hooks/use-booking-dialogs';

type Props = {
    dialogs: UseBookingDialogsReturn;
    maxUploadKb: number;
};

export function BookingActionDialogs({ dialogs, maxUploadKb }: Props) {
    const { current, visible, setVisible } = dialogs;

    if (current === null) {
        return null;
    }

    const shared = {
        booking: current.booking,
        open: visible,
        onOpenChange: setVisible,
    };

    switch (current.type) {
        case 'pickup':
        case 'return':
            return (
                <BookingHandoverDialog
                    {...shared}
                    key={current.key}
                    stage={current.type}
                    maxUploadKb={maxUploadKb}
                />
            );
        case 'extend':
            return <BookingExtendDialog {...shared} key={current.key} />;
        case 'cancel':
            return <BookingCancelDialog {...shared} key={current.key} />;
    }
}

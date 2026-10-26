import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormDialog } from '@/components/form-dialog';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DateTimePicker } from '@/components/date-picker/date-time-picker';
import { FormField } from '@/components/form/form-field';
import { Textarea } from '@/components/ui/textarea';
import { useDateFormat } from '@/hooks/use-date-format';
import { useLongBookingConfirm } from '@/hooks/use-long-booking-confirm';
import { toZonedIso } from '@/lib/datetime';
import { extend } from '@/routes/bookings';
import type { Booking } from '@/types';

type Props = {
    booking: Booking;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

type ExtendForm = {
    new_planned_return_at: string;
    reason: string;
};

/**
 * Perpanjangan rencana kembali (prd.md 3.3): wajib lebih lambat dari rencana
 * saat ini dan disertai alasan.
 */
export function BookingExtendDialog({ booking, open, onOpenChange }: Props) {
    const { timezone, bookingRules } = usePage().props;
    const { formatDateTime, toDateTimeLocal } = useDateFormat();
    const current = toDateTimeLocal(booking.planned_return_at);
    const form = useForm<ExtendForm>({
        new_planned_return_at: current,
        reason: '',
    });

    const longBooking = useLongBookingConfirm();

    const save = () => {
        form.transform((data) => ({
            ...data,
            new_planned_return_at: toZonedIso(
                data.new_planned_return_at,
                timezone,
            ),
        }));
        form.submit(extend(booking.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    // Durasi total dihitung dari waktu mulai sampai rencana kembali baru.
    const submit = (event: FormEvent) => {
        event.preventDefault();
        longBooking.confirmIfLong(
            toDateTimeLocal(booking.start_at),
            form.data.new_planned_return_at,
            save,
        );
    };

    return (
        <>
            <FormDialog
                open={open}
                onOpenChange={onOpenChange}
                title="Perpanjang Peminjaman"
                description={
                    <>
                        Rencana kembali {booking.code} ({booking.car.brand}{' '}
                        {booking.car.model} · {booking.car.plate_number}) saat
                        ini {formatDateTime(booking.planned_return_at)}. Pilih
                        waktu baru beserta alasannya.
                    </>
                }
                onSubmit={submit}
                submitLabel="Simpan Perpanjangan"
                processing={form.processing}
                submitTestId="extend-submit"
            >
                <div className="grid gap-5">
                    <FormField
                        id="extend-return-at"
                        label="Rencana kembali baru"
                        required
                        error={form.errors.new_planned_return_at}
                    >
                        <DateTimePicker
                            id="extend-return-at"
                            value={form.data.new_planned_return_at}
                            min={current}
                            onChange={(value) =>
                                form.setData('new_planned_return_at', value)
                            }
                            invalid={!!form.errors.new_planned_return_at}
                        />
                    </FormField>

                    <FormField
                        id="extend-reason"
                        label="Alasan"
                        required
                        error={form.errors.reason}
                    >
                        <Textarea
                            id="extend-reason"
                            name="reason"
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData('reason', e.target.value)
                            }
                            required
                            minLength={bookingRules.reasonMinLength}
                            maxLength={500}
                            placeholder="Misalnya: rapat dengan klien diperpanjang…"
                            aria-invalid={!!form.errors.reason || undefined}
                        />
                    </FormField>
                </div>
            </FormDialog>
            <ConfirmDialog {...longBooking.dialog} />
        </>
    );
}

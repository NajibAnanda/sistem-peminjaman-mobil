import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormDialog } from '@/components/form-dialog';
import { FormField } from '@/components/form/form-field';
import { Textarea } from '@/components/ui/textarea';
import { cancel } from '@/routes/bookings';
import type { Booking } from '@/types';

type Props = {
    booking: Booking;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/**
 * Konfirmasi pembatalan (aksi berisiko, AGENTS.md 8.3) sekaligus form alasan
 * yang wajib diisi (prd.md FR-14).
 */
export function BookingCancelDialog({ booking, open, onOpenChange }: Props) {
    const { reasonMinLength } = usePage().props.bookingRules;
    const form = useForm({ reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(cancel(booking.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <FormDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Batalkan peminjaman?"
            description={
                <>
                    {booking.code} untuk {booking.employee.name} (
                    {booking.car.plate_number}) akan dibatalkan dan tidak dapat
                    diaktifkan kembali.
                </>
            }
            onSubmit={submit}
            submitLabel="Batalkan Peminjaman"
            submitVariant="destructive"
            processing={form.processing}
            submitTestId="cancel-submit"
        >
            <FormField
                id="cancel-reason"
                label="Alasan pembatalan"
                required
                error={form.errors.reason}
                hint={`Minimal ${reasonMinLength} karakter.`}
            >
                <Textarea
                    id="cancel-reason"
                    value={form.data.reason}
                    onChange={(e) => form.setData('reason', e.target.value)}
                    required
                    minLength={reasonMinLength}
                    maxLength={500}
                    placeholder="Misalnya: kegiatan ditunda oleh atasan"
                    aria-invalid={!!form.errors.reason || undefined}
                    aria-describedby="cancel-reason-hint"
                />
            </FormField>
        </FormDialog>
    );
}

import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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
    const form = useForm({ reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(cancel(booking.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => !form.processing && onOpenChange(next)}
        >
            <DialogContent
                className="sm:max-w-md"
                onInteractOutside={(event) => event.preventDefault()}
            >
                <form onSubmit={submit} className="grid gap-6">
                    <DialogHeader>
                        <DialogTitle>Batalkan peminjaman?</DialogTitle>
                        <DialogDescription>
                            {booking.code} untuk {booking.employee.name} (
                            {booking.car.plate_number}) akan dibatalkan dan
                            tidak dapat diaktifkan kembali.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="cancel-reason">Alasan pembatalan</Label>
                        <Textarea
                            id="cancel-reason"
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData('reason', e.target.value)
                            }
                            required
                            maxLength={500}
                            placeholder="Misalnya: kegiatan ditunda oleh atasan"
                        />
                        <InputError message={form.errors.reason} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={form.processing}
                        >
                            Kembali
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                            data-test="cancel-submit"
                        >
                            {form.processing && <Spinner />}
                            Batalkan Peminjaman
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

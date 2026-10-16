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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useDateFormat } from '@/hooks/use-date-format';
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
    const { formatDateTime, toDateTimeLocal } = useDateFormat();
    const current = toDateTimeLocal(booking.planned_return_at);
    const form = useForm<ExtendForm>({
        new_planned_return_at: current,
        reason: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(extend(booking.id), {
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
                        <DialogTitle>Perpanjang Peminjaman</DialogTitle>
                        <DialogDescription>
                            {booking.code} · {booking.car.plate_number}. Rencana
                            kembali saat ini{' '}
                            {formatDateTime(booking.planned_return_at)}.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="extend-return-at">
                                Rencana kembali baru
                            </Label>
                            <Input
                                id="extend-return-at"
                                type="datetime-local"
                                value={form.data.new_planned_return_at}
                                min={current}
                                onChange={(e) =>
                                    form.setData(
                                        'new_planned_return_at',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={form.errors.new_planned_return_at}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="extend-reason">Alasan</Label>
                            <Textarea
                                id="extend-reason"
                                value={form.data.reason}
                                onChange={(e) =>
                                    form.setData('reason', e.target.value)
                                }
                                required
                                maxLength={500}
                                placeholder="Misalnya: rapat dengan klien diperpanjang"
                            />
                            <InputError message={form.errors.reason} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={form.processing}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="extend-submit"
                        >
                            {form.processing && <Spinner />}
                            Simpan Perpanjangan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

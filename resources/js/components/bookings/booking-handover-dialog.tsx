import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import type { CapturedPhoto } from '@/components/photo-capture';
import { PhotoCapture } from '@/components/photo-capture';
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
import { pickup, returnMethod } from '@/routes/bookings';
import type { Booking, PhotoSource } from '@/types';

export type HandoverStage = 'pickup' | 'return';

type Props = {
    stage: HandoverStage;
    booking: Booking;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    maxUploadKb: number;
};

type HandoverForm = {
    photo: File | null;
    source: PhotoSource | '';
    return_notes: string;
};

const copy: Record<
    HandoverStage,
    { title: string; result: string; submit: string }
> = {
    pickup: {
        title: 'Serah Terima Ambil',
        result: 'Status menjadi Aktif setelah foto disimpan.',
        submit: 'Simpan Serah Terima Ambil',
    },
    return: {
        title: 'Serah Terima Kembali',
        result: 'Status menjadi Selesai setelah foto disimpan.',
        submit: 'Simpan Serah Terima Kembali',
    },
};

/**
 * Satu form serah terima yang menyesuaikan status (prd.md 3.2): tepat 1 foto
 * kondisi mobil, ditambah catatan kondisi opsional saat kembali.
 */
export function BookingHandoverDialog({
    stage,
    booking,
    open,
    onOpenChange,
    maxUploadKb,
}: Props) {
    const [photo, setPhoto] = useState<CapturedPhoto | null>(null);
    const form = useForm<HandoverForm>({
        photo: null,
        source: '',
        return_notes: '',
    });
    const text = copy[stage];

    const changePhoto = (next: CapturedPhoto | null) => {
        setPhoto(next);
        form.setData((data) => ({
            ...data,
            photo: next?.file ?? null,
            source: next?.source ?? '',
        }));
        form.clearErrors('photo', 'source');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(
            stage === 'pickup' ? pickup(booking.id) : returnMethod(booking.id),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            },
        );
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => !form.processing && onOpenChange(next)}
        >
            <DialogContent
                className="sm:max-w-lg"
                onInteractOutside={(event) => event.preventDefault()}
            >
                <form onSubmit={submit} className="grid gap-6">
                    <DialogHeader>
                        <DialogTitle>{text.title}</DialogTitle>
                        <DialogDescription>
                            {booking.code} · {booking.car.plate_number} ·{' '}
                            {booking.employee.name}. {text.result}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label>Foto kondisi mobil</Label>
                            <PhotoCapture
                                value={photo}
                                onChange={changePhoto}
                                maxUploadKb={maxUploadKb}
                                error={form.errors.photo ?? form.errors.source}
                                disabled={form.processing}
                            />
                        </div>

                        {stage === 'return' && (
                            <div className="grid gap-2">
                                <Label htmlFor="return-notes">
                                    Catatan kondisi (opsional)
                                </Label>
                                <Textarea
                                    id="return-notes"
                                    value={form.data.return_notes}
                                    onChange={(e) =>
                                        form.setData(
                                            'return_notes',
                                            e.target.value,
                                        )
                                    }
                                    maxLength={1000}
                                    placeholder="Misalnya: baret baru di pintu kiri belakang"
                                />
                                <InputError
                                    message={form.errors.return_notes}
                                />
                            </div>
                        )}
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
                            disabled={form.processing || photo === null}
                            data-test="handover-submit"
                        >
                            {form.processing && <Spinner />}
                            {text.submit}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

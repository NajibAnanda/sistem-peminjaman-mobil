import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { FormDialog } from '@/components/form-dialog';
import { FormField } from '@/components/form/form-field';
import type { CapturedPhoto } from '@/components/photo-capture';
import { PhotoCapture } from '@/components/photo-capture';
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
        <FormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={text.title}
            description={
                <>
                    {booking.code} · {booking.car.brand} {booking.car.model} (
                    {booking.car.plate_number}) · {booking.employee.name}.{' '}
                    {text.result}
                </>
            }
            onSubmit={submit}
            submitLabel={text.submit}
            processing={form.processing}
            submitTestId="handover-submit"
        >
            <div className="grid gap-5">
                <FormField
                    id="handover-photo"
                    label="Foto kondisi mobil"
                    required
                >
                    <PhotoCapture
                        id="handover-photo"
                        value={photo}
                        onChange={changePhoto}
                        maxUploadKb={maxUploadKb}
                        error={form.errors.photo ?? form.errors.source}
                        disabled={form.processing}
                    />
                </FormField>

                {stage === 'return' && (
                    <FormField
                        id="return-notes"
                        label="Catatan kondisi (opsional)"
                        error={form.errors.return_notes}
                    >
                        <Textarea
                            id="return-notes"
                            name="return_notes"
                            value={form.data.return_notes}
                            onChange={(e) =>
                                form.setData('return_notes', e.target.value)
                            }
                            maxLength={1000}
                            placeholder="Misalnya: baret baru di pintu kiri belakang…"
                            aria-invalid={
                                Boolean(form.errors.return_notes) || undefined
                            }
                        />
                    </FormField>
                )}
            </div>
        </FormDialog>
    );
}

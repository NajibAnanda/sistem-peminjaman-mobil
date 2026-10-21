import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormDialog } from '@/components/form-dialog';
import { FormField } from '@/components/form/form-field';
import { FormGrid } from '@/components/form/form-grid';
import { Input } from '@/components/ui/input';
import { store, update } from '@/routes/cars';
import type { Car } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = tambah mobil baru */
    car: Car | null;
};

type CarForm = {
    plate_number: string;
    brand: string;
    model: string;
};

export function CarFormDialog({ open, onOpenChange, car }: Props) {
    const isEdit = car !== null;
    const form = useForm<CarForm>({
        plate_number: car?.plate_number ?? '',
        brand: car?.brand ?? '',
        model: car?.model ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(isEdit ? update(car.id) : store(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <FormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Data Mobil' : 'Tambah Mobil'}
            description={
                isEdit
                    ? 'Perbarui plat nomor, merk, atau tipe mobil sesuai data kendaraan terbaru.'
                    : 'Daftarkan mobil dinas baru; foto referensi dapat ditambahkan setelah data disimpan.'
            }
            onSubmit={submit}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Tambah Mobil'}
            processing={form.processing}
            submitTestId="car-form-submit"
        >
            <FormGrid>
                <FormField
                    id="car-plate"
                    label="Plat nomor"
                    required
                    error={form.errors.plate_number}
                    hint="Contoh: B 1234 ABC. Disimpan dalam huruf besar."
                    span="full"
                >
                    <Input
                        id="car-plate"
                        value={form.data.plate_number}
                        onChange={(e) =>
                            form.setData('plate_number', e.target.value)
                        }
                        autoFocus
                        required
                        maxLength={15}
                        autoComplete="off"
                        placeholder="B 1234 ABC"
                        className="uppercase"
                        aria-invalid={!!form.errors.plate_number || undefined}
                        aria-describedby="car-plate-hint"
                    />
                </FormField>

                <FormField
                    id="car-brand"
                    label="Merk"
                    required
                    error={form.errors.brand}
                >
                    <Input
                        id="car-brand"
                        value={form.data.brand}
                        onChange={(e) => form.setData('brand', e.target.value)}
                        required
                        maxLength={50}
                        autoComplete="off"
                        placeholder="Toyota"
                        aria-invalid={!!form.errors.brand || undefined}
                    />
                </FormField>

                <FormField
                    id="car-model"
                    label="Tipe"
                    required
                    error={form.errors.model}
                >
                    <Input
                        id="car-model"
                        value={form.data.model}
                        onChange={(e) => form.setData('model', e.target.value)}
                        required
                        maxLength={50}
                        autoComplete="off"
                        placeholder="Avanza"
                        aria-invalid={!!form.errors.model || undefined}
                    />
                </FormField>
            </FormGrid>
        </FormDialog>
    );
}

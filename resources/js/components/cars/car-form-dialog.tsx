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
                        <DialogTitle>
                            {isEdit ? 'Edit Data Mobil' : 'Tambah Mobil'}
                        </DialogTitle>
                        <DialogDescription>
                            {isEdit
                                ? 'Perbarui plat nomor, merk, atau tipe mobil.'
                                : 'Foto referensi dapat ditambahkan setelah mobil disimpan.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="car-plate">Plat nomor</Label>
                            <Input
                                id="car-plate"
                                value={form.data.plate_number}
                                onChange={(e) =>
                                    form.setData('plate_number', e.target.value)
                                }
                                autoFocus
                                required
                                autoComplete="off"
                                placeholder="B 1234 ABC"
                                className="uppercase"
                            />
                            <InputError message={form.errors.plate_number} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="car-brand">Merk</Label>
                                <Input
                                    id="car-brand"
                                    value={form.data.brand}
                                    onChange={(e) =>
                                        form.setData('brand', e.target.value)
                                    }
                                    required
                                    autoComplete="off"
                                    placeholder="Toyota"
                                />
                                <InputError message={form.errors.brand} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="car-model">Tipe</Label>
                                <Input
                                    id="car-model"
                                    value={form.data.model}
                                    onChange={(e) =>
                                        form.setData('model', e.target.value)
                                    }
                                    required
                                    autoComplete="off"
                                    placeholder="Avanza"
                                />
                                <InputError message={form.errors.model} />
                            </div>
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
                            data-test="car-form-submit"
                        >
                            {form.processing && <Spinner />}
                            {isEdit ? 'Simpan Perubahan' : 'Tambah Mobil'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

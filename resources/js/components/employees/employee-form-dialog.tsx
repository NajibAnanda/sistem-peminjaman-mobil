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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/employees';
import type { Employee, Option } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = tambah karyawan baru */
    employee: Employee | null;
    divisions: Option[];
};

type EmployeeForm = {
    employee_number: string;
    name: string;
    division: string;
    phone: string;
};

export function EmployeeFormDialog({
    open,
    onOpenChange,
    employee,
    divisions,
}: Props) {
    const isEdit = employee !== null;
    const form = useForm<EmployeeForm>({
        employee_number: employee?.employee_number ?? '',
        name: employee?.name ?? '',
        division: employee?.division ?? '',
        phone: employee?.phone ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(isEdit ? update(employee.id) : store(), {
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
                            {isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan'}
                        </DialogTitle>
                        <DialogDescription>
                            Karyawan peminjam tidak memiliki akun dan tidak
                            login ke aplikasi.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="employee-number">NIP</Label>
                            <Input
                                id="employee-number"
                                value={form.data.employee_number}
                                onChange={(e) =>
                                    form.setData(
                                        'employee_number',
                                        e.target.value,
                                    )
                                }
                                autoFocus
                                required
                                autoComplete="off"
                                className="uppercase"
                                placeholder="K2024001"
                            />
                            <p className="text-xs text-muted-foreground">
                                Huruf dan angka saja, tanpa spasi atau simbol.
                            </p>
                            <InputError message={form.errors.employee_number} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="employee-name">Nama</Label>
                            <Input
                                id="employee-name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                                autoComplete="off"
                                placeholder="Nama lengkap"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="employee-division">
                                    Divisi
                                </Label>
                                <Select
                                    value={form.data.division}
                                    onValueChange={(value) =>
                                        form.setData('division', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="employee-division"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih divisi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {divisions.map((division) => (
                                            <SelectItem
                                                key={division.value}
                                                value={division.value}
                                            >
                                                {division.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.division} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="employee-phone">No HP</Label>
                                <Input
                                    id="employee-phone"
                                    type="tel"
                                    inputMode="tel"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                    required
                                    autoComplete="off"
                                    placeholder="0812xxxxxxxx"
                                />
                                <InputError message={form.errors.phone} />
                            </div>
                        </div>
                        <p className="-mt-2 text-xs text-muted-foreground">
                            No HP boleh ditulis 08xx atau +62xx; disimpan dalam
                            format 08xx.
                        </p>
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
                            data-test="employee-form-submit"
                        >
                            {form.processing && <Spinner />}
                            {isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

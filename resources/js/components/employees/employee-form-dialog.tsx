import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormDialog } from '@/components/form-dialog';
import { FormField } from '@/components/form/form-field';
import { FormGrid } from '@/components/form/form-grid';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
        <FormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan'}
            description={
                isEdit
                    ? 'Perbarui identitas, divisi, atau nomor HP karyawan sesuai data terbaru.'
                    : 'Tambahkan karyawan yang dapat meminjam mobil dinas; karyawan tidak memiliki akun di sistem.'
            }
            onSubmit={submit}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan'}
            processing={form.processing}
            submitTestId="employee-form-submit"
        >
            <FormGrid>
                <FormField
                    id="employee-number"
                    label="NIP"
                    required
                    error={form.errors.employee_number}
                    hint="Huruf dan angka saja, tanpa spasi atau simbol."
                >
                    <Input
                        id="employee-number"
                        value={form.data.employee_number}
                        onChange={(e) =>
                            form.setData('employee_number', e.target.value)
                        }
                        autoFocus
                        required
                        maxLength={30}
                        autoComplete="off"
                        spellCheck={false}
                        className="uppercase"
                        placeholder="K2024001"
                        aria-invalid={
                            !!form.errors.employee_number || undefined
                        }
                        aria-describedby="employee-number-hint"
                    />
                </FormField>

                <FormField
                    id="employee-name"
                    label="Nama"
                    required
                    error={form.errors.name}
                >
                    <Input
                        id="employee-name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                        maxLength={255}
                        autoComplete="off"
                        placeholder="Nama lengkap"
                        aria-invalid={!!form.errors.name || undefined}
                    />
                </FormField>

                <FormField
                    id="employee-division"
                    label="Divisi"
                    required
                    error={form.errors.division}
                >
                    <Select
                        value={form.data.division}
                        onValueChange={(value) =>
                            form.setData('division', value)
                        }
                    >
                        <SelectTrigger
                            id="employee-division"
                            className="w-full"
                            aria-invalid={!!form.errors.division || undefined}
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
                </FormField>

                <FormField
                    id="employee-phone"
                    label="No HP"
                    required
                    error={form.errors.phone}
                    hint="Boleh ditulis 08xx atau +62xx; disimpan dalam format 08xx."
                >
                    <Input
                        id="employee-phone"
                        type="tel"
                        inputMode="tel"
                        value={form.data.phone}
                        onChange={(e) => form.setData('phone', e.target.value)}
                        required
                        maxLength={20}
                        autoComplete="off"
                        placeholder="0812xxxxxxxx"
                        aria-invalid={!!form.errors.phone || undefined}
                        aria-describedby="employee-phone-hint"
                    />
                </FormField>
            </FormGrid>
        </FormDialog>
    );
}

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
import { store, update } from '@/routes/staff';
import type { Option, StaffMember, StaffRole } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = tambah akun baru */
    staff: StaffMember | null;
    roles: Option<StaffRole>[];
};

type StaffForm = {
    name: string;
    email: string;
    role: StaffRole;
};

export function StaffFormDialog({ open, onOpenChange, staff, roles }: Props) {
    const isEdit = staff !== null;
    const form = useForm<StaffForm>({
        name: staff?.name ?? '',
        email: staff?.email ?? '',
        role: staff?.role ?? 'admin',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(isEdit ? update(staff.id) : store(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <FormDialog
            open={open}
            onOpenChange={onOpenChange}
            title={isEdit ? 'Edit Akun Petugas' : 'Tambah Akun Petugas'}
            description={
                isEdit
                    ? 'Perbarui nama, email, atau peran akun petugas sesuai tugasnya saat ini.'
                    : 'Buat akun petugas baru. Password sementara dibuat otomatis dan wajib diganti saat login pertama.'
            }
            onSubmit={submit}
            submitLabel={isEdit ? 'Simpan Perubahan' : 'Buat Akun'}
            processing={form.processing}
            submitTestId="staff-form-submit"
        >
            <FormGrid>
                <FormField
                    id="staff-name"
                    label="Nama"
                    required
                    error={form.errors.name}
                >
                    <Input
                        id="staff-name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        autoFocus
                        required
                        maxLength={255}
                        autoComplete="off"
                        placeholder="Nama lengkap"
                        aria-invalid={!!form.errors.name || undefined}
                    />
                </FormField>

                <FormField
                    id="staff-email"
                    label="Email"
                    required
                    error={form.errors.email}
                >
                    <Input
                        id="staff-email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        required
                        maxLength={255}
                        autoComplete="off"
                        placeholder="nama@perusahaan.co.id"
                        aria-invalid={!!form.errors.email || undefined}
                    />
                </FormField>

                <FormField
                    id="staff-role"
                    label="Peran"
                    required
                    error={form.errors.role}
                    hint={
                        staff?.is_self
                            ? 'Peran akun Anda sendiri tidak dapat diubah.'
                            : undefined
                    }
                    span="full"
                >
                    <Select
                        value={form.data.role}
                        onValueChange={(value) =>
                            form.setData('role', value as StaffRole)
                        }
                        disabled={staff?.is_self}
                    >
                        <SelectTrigger
                            id="staff-role"
                            className="w-full"
                            aria-describedby={
                                staff?.is_self ? 'staff-role-hint' : undefined
                            }
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {roles.map((role) => (
                                <SelectItem key={role.value} value={role.value}>
                                    {role.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </FormField>
            </FormGrid>
        </FormDialog>
    );
}

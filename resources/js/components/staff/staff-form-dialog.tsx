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
                            {isEdit
                                ? 'Edit Akun Petugas'
                                : 'Tambah Akun Petugas'}
                        </DialogTitle>
                        <DialogDescription>
                            {isEdit
                                ? 'Perbarui nama, email, atau peran akun.'
                                : 'Password sementara akan dibuat otomatis dan wajib diganti saat login pertama.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="staff-name">Nama</Label>
                            <Input
                                id="staff-name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                autoFocus
                                required
                                autoComplete="off"
                                placeholder="Nama lengkap"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="staff-email">Email</Label>
                            <Input
                                id="staff-email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                required
                                autoComplete="off"
                                placeholder="nama@perusahaan.co.id"
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="staff-role">Peran</Label>
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
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((role) => (
                                        <SelectItem
                                            key={role.value}
                                            value={role.value}
                                        >
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {staff?.is_self ? (
                                <p className="text-xs text-muted-foreground">
                                    Peran akun Anda sendiri tidak dapat diubah.
                                </p>
                            ) : (
                                <p className="text-xs text-muted-foreground">
                                    Pengelola dapat mengelola akun petugas dan
                                    melihat log aktivitas.
                                </p>
                            )}
                            <InputError message={form.errors.role} />
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
                            data-test="staff-form-submit"
                        >
                            {form.processing && <Spinner />}
                            {isEdit ? 'Simpan Perubahan' : 'Buat Akun'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

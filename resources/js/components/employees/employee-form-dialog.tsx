import { router, useForm } from '@inertiajs/react';
import { Check, Plus, Trash2, X } from 'lucide-react';
import { type FormEvent, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormDialog } from '@/components/form-dialog';
import { FormField } from '@/components/form/form-field';
import { FormGrid } from '@/components/form/form-grid';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destroy as destroyDivision,
    store as storeDivision,
} from '@/routes/divisions';
import { store, update } from '@/routes/employees';
import type { DivisionOption, Employee } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = tambah karyawan baru */
    employee: Employee | null;
    divisions: DivisionOption[];
};

type EmployeeForm = {
    name: string;
    division: string;
    phone: string;
};

/**
 * Menyaring input no HP agar hanya berisi angka dan tanda '+' di awal (Opsi A).
 * Karakter pemisah (spasi, strip, titik, kurung) dibersihkan otomatis,
 * dan input '628...' otomatis diawali tanda '+'.
 */
function sanitizePhoneNumber(raw: string): string {
    const cleaned = raw.replace(/[\s.\-()]/g, '');

    if (cleaned.startsWith('+')) {
        return '+' + cleaned.slice(1).replace(/\D/g, '').slice(0, 15);
    }

    if (/^628\d*/.test(cleaned)) {
        return '+' + cleaned.replace(/\D/g, '').slice(0, 15);
    }

    return cleaned.replace(/\D/g, '').slice(0, 15);
}

export function EmployeeFormDialog({
    open,
    onOpenChange,
    employee,
    divisions,
}: Props) {
    const isEdit = employee !== null;
    const form = useForm<EmployeeForm>({
        name: employee?.name ?? '',
        division: employee?.division ?? '',
        phone: employee?.phone ?? '',
    });

    const [isSelectOpen, setIsSelectOpen] = useState(false);
    const [isAddingDivision, setIsAddingDivision] = useState(false);
    const [newDivisionName, setNewDivisionName] = useState('');
    const newDivisionInputRef = useRef<HTMLInputElement>(null);

    const [divisionToDelete, setDivisionToDelete] =
        useState<DivisionOption | null>(null);
    const [isDeletingDivision, setIsDeletingDivision] = useState(false);

    const [divisionOptions, setDivisionOptions] = useState<DivisionOption[]>(
        () => {
            const list = [...divisions];
            if (
                employee?.division &&
                !list.some((d) => d.value === employee.division)
            ) {
                list.push({
                    value: employee.division,
                    label: employee.division,
                    can_delete: false,
                });
            }
            return list;
        },
    );

    useEffect(() => {
        setDivisionOptions(() => {
            const map = new Map<string, DivisionOption>();
            for (const d of divisions) {
                map.set(d.value, d);
            }
            if (employee?.division && !map.has(employee.division)) {
                map.set(employee.division, {
                    value: employee.division,
                    label: employee.division,
                    can_delete: false,
                });
            }
            return Array.from(map.values()).sort((a, b) =>
                a.label.localeCompare(b.label),
            );
        });
    }, [divisions, employee?.division]);

    useEffect(() => {
        if (isAddingDivision) {
            requestAnimationFrame(() => {
                newDivisionInputRef.current?.focus();
            });
        }
    }, [isAddingDivision]);

    const handleSelectOpenChange = (openState: boolean) => {
        setIsSelectOpen(openState);
        if (!openState) {
            setIsAddingDivision(false);
            setNewDivisionName('');
        }
    };

    const handleSaveNewDivision = () => {
        const trimmed = newDivisionName.trim();
        if (!trimmed) {
            return;
        }

        const existing = divisionOptions.find(
            (opt) => opt.value.toLowerCase() === trimmed.toLowerCase(),
        );

        if (existing) {
            form.setData('division', existing.value);
            setNewDivisionName('');
            setIsAddingDivision(false);
            setIsSelectOpen(false);
            return;
        }

        router.post(
            storeDivision(),
            { name: trimmed },
            {
                preserveScroll: true,
                onSuccess: () => {
                    form.setData('division', trimmed);
                    setNewDivisionName('');
                    setIsAddingDivision(false);
                    setIsSelectOpen(false);
                },
                onError: (errors) => {
                    if (errors.name) {
                        toast.error(errors.name);
                    }
                },
            },
        );
    };

    const handleCancelNewDivision = () => {
        setNewDivisionName('');
        setIsAddingDivision(false);
    };

    const handleRequestDeleteDivision = (division: DivisionOption) => {
        setIsSelectOpen(false);
        setDivisionToDelete(division);
    };

    const handleConfirmDeleteDivision = () => {
        if (!divisionToDelete) {
            return;
        }

        const targetValue = divisionToDelete.value;
        setIsDeletingDivision(true);

        router.delete(destroyDivision(targetValue), {
            preserveScroll: true,
            onSuccess: (page) => {
                // Periksa apakah divisi benar-benar terhapus di server (tidak ditolak karena masih dipakai).
                const freshDivisions = (
                    page.props as unknown as { divisions?: DivisionOption[] }
                ).divisions;
                const isStillPresent = freshDivisions?.some(
                    (d) => d.value === targetValue,
                );

                if (!isStillPresent) {
                    if (form.data.division === targetValue) {
                        form.setData('division', '');
                    }
                    if (freshDivisions) {
                        setDivisionOptions(freshDivisions);
                    } else {
                        setDivisionOptions((prev) =>
                            prev.filter((d) => d.value !== targetValue),
                        );
                    }
                }
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors)[0];
                if (errorMessage) {
                    toast.error(errorMessage);
                }
            },
            onFinish: () => {
                setIsDeletingDivision(false);
                setDivisionToDelete(null);
            },
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(isEdit ? update(employee.id) : store(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <>
            <FormDialog
                open={open}
                onOpenChange={onOpenChange}
                title={isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan'}
                description={
                    isEdit
                        ? 'Perbarui identitas, divisi, atau nomor HP karyawan sesuai data terbaru.'
                        : 'Tambahkan karyawan yang dapat meminjam mobil dinas. Karyawan tidak memiliki akun login.'
                }
                onSubmit={submit}
                submitLabel={isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan'}
                processing={form.processing}
                submitTestId="employee-form-submit"
            >
                <FormGrid>
                    <FormField
                        id="employee-name"
                        label="Nama"
                        required
                        span="full"
                        error={form.errors.name}
                    >
                        <Input
                            id="employee-name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            autoFocus
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
                            open={isSelectOpen}
                            onOpenChange={handleSelectOpenChange}
                            value={form.data.division}
                            onValueChange={(value) =>
                                form.setData('division', value)
                            }
                        >
                            <SelectTrigger
                                id="employee-division"
                                className="w-full"
                                aria-invalid={
                                    !!form.errors.division || undefined
                                }
                            >
                                <SelectValue placeholder="Pilih divisi" />
                            </SelectTrigger>
                            <SelectContent>
                                {divisionOptions.map((division) => (
                                    <SelectItem
                                        key={division.value}
                                        value={division.value}
                                        action={
                                            division.can_delete ? (
                                                <button
                                                    type="button"
                                                    className="flex size-5 items-center justify-center rounded text-destructive transition-colors hover:bg-destructive/15 focus-visible:outline-none"
                                                    title={`Hapus divisi ${division.label}`}
                                                    aria-label={`Hapus divisi ${division.label}`}
                                                    onPointerDown={(e) => {
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                    }}
                                                    onMouseDown={(e) => {
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                    }}
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                        handleRequestDeleteDivision(
                                                            division,
                                                        );
                                                    }}
                                                >
                                                    <Trash2 className="size-3.5" />
                                                </button>
                                            ) : undefined
                                        }
                                    >
                                        {division.label}
                                    </SelectItem>
                                ))}
                                <SelectSeparator />
                                {isAddingDivision ? (
                                    <div
                                        className="flex items-center gap-1 p-1"
                                        onPointerDown={(e) =>
                                            e.stopPropagation()
                                        }
                                        onMouseDown={(e) => e.stopPropagation()}
                                        onKeyDown={(e) => e.stopPropagation()}
                                    >
                                        <Input
                                            ref={newDivisionInputRef}
                                            value={newDivisionName}
                                            onChange={(e) =>
                                                setNewDivisionName(
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Nama divisi..."
                                            className="h-7 min-w-0 flex-1 px-2 text-xs"
                                            maxLength={50}
                                            onKeyDown={(e) => {
                                                e.stopPropagation();
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    handleSaveNewDivision();
                                                } else if (e.key === 'Escape') {
                                                    e.preventDefault();
                                                    handleCancelNewDivision();
                                                }
                                            }}
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-xs"
                                            className="shrink-0 text-success hover:bg-success/15 hover:text-success"
                                            title="Simpan divisi"
                                            aria-label="Simpan divisi"
                                            disabled={!newDivisionName.trim()}
                                            onPointerDown={(e) =>
                                                e.stopPropagation()
                                            }
                                            onMouseDown={(e) =>
                                                e.stopPropagation()
                                            }
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                handleSaveNewDivision();
                                            }}
                                        >
                                            <Check className="size-3.5" />
                                            <span className="sr-only">
                                                Simpan divisi
                                            </span>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-xs"
                                            className="shrink-0 text-muted-foreground hover:bg-muted hover:text-foreground"
                                            title="Batal"
                                            aria-label="Batal"
                                            onPointerDown={(e) =>
                                                e.stopPropagation()
                                            }
                                            onMouseDown={(e) =>
                                                e.stopPropagation()
                                            }
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                handleCancelNewDivision();
                                            }}
                                        >
                                            <X className="size-3.5" />
                                            <span className="sr-only">
                                                Batal
                                            </span>
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="p-1">
                                        <button
                                            type="button"
                                            className="flex w-full cursor-pointer items-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium text-primary transition-colors outline-none hover:bg-primary/10 hover:text-primary focus-visible:bg-primary/10 focus-visible:text-primary"
                                            onPointerDown={(e) =>
                                                e.stopPropagation()
                                            }
                                            onMouseDown={(e) =>
                                                e.stopPropagation()
                                            }
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setIsAddingDivision(true);
                                            }}
                                        >
                                            <Plus className="size-3.5 shrink-0" />
                                            <span>Tambah Divisi Baru</span>
                                        </button>
                                    </div>
                                )}
                            </SelectContent>
                        </Select>
                    </FormField>

                    <FormField
                        id="employee-phone"
                        label="No HP"
                        required
                        error={form.errors.phone}
                    >
                        <Input
                            id="employee-phone"
                            type="tel"
                            inputMode="tel"
                            value={form.data.phone}
                            onChange={(e) =>
                                form.setData(
                                    'phone',
                                    sanitizePhoneNumber(e.target.value),
                                )
                            }
                            required
                            maxLength={16}
                            autoComplete="off"
                            placeholder="0812xxxxxxxx"
                            aria-invalid={!!form.errors.phone || undefined}
                        />
                    </FormField>
                </FormGrid>
            </FormDialog>

            <ConfirmDialog
                open={divisionToDelete !== null}
                onOpenChange={(nextOpen) => {
                    if (!nextOpen) {
                        setDivisionToDelete(null);
                    }
                }}
                title="Hapus Divisi"
                description={
                    <span>
                        Apakah Anda yakin ingin menghapus divisi{' '}
                        <strong className="text-foreground">
                            {divisionToDelete?.label}
                        </strong>{' '}
                        dari daftar pilihan?
                    </span>
                }
                confirmLabel="Hapus Divisi"
                destructive
                processing={isDeletingDivision}
                onConfirm={handleConfirmDeleteDivision}
            />
        </>
    );
}

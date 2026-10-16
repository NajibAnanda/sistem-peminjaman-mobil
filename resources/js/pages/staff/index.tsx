import { Head, router } from '@inertiajs/react';
import { SearchX, UserPlus, Users } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { PaginationNav } from '@/components/pagination-nav';
import type { StaffAction } from '@/components/staff/staff-actions-menu';
import {
    hasActiveFilters,
    StaffFilters,
} from '@/components/staff/staff-filters';
import { StaffFormDialog } from '@/components/staff/staff-form-dialog';
import { StaffTable } from '@/components/staff/staff-table';
import { TemporaryPasswordDialog } from '@/components/staff/temporary-password-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useFlashData } from '@/hooks/use-flash-data';
import { useNavigationLoading } from '@/hooks/use-navigation-loading';
import { cn } from '@/lib/utils';
import { activate, deactivate, index, resetPassword } from '@/routes/staff';
import type {
    StaffIndexProps,
    StaffMember,
    TemporaryPasswordFlash,
} from '@/types';

type ConfirmableAction = Exclude<StaffAction, 'edit'>;

const confirmations: Record<
    ConfirmableAction,
    {
        title: string;
        description: (staff: StaffMember) => string;
        confirmLabel: string;
        destructive: boolean;
        route: typeof deactivate;
    }
> = {
    deactivate: {
        title: 'Nonaktifkan akun?',
        description: (staff) =>
            `${staff.name} tidak akan bisa login dan langsung dikeluarkan dari aplikasi. Data dan riwayatnya tetap tersimpan, dan akun dapat diaktifkan kembali.`,
        confirmLabel: 'Nonaktifkan',
        destructive: true,
        route: deactivate,
    },
    activate: {
        title: 'Aktifkan kembali akun?',
        description: (staff) =>
            `${staff.name} dapat login kembali dengan password yang terakhir dipakai.`,
        confirmLabel: 'Aktifkan',
        destructive: false,
        route: activate,
    },
    reset: {
        title: 'Reset password?',
        description: (staff) =>
            `Password ${staff.name} akan diganti dengan password sementara dan semua sesinya diakhiri. Password sementara wajib diganti saat login berikutnya.`,
        confirmLabel: 'Reset password',
        destructive: true,
        route: resetPassword,
    },
};

export default function StaffIndex({ staff, filters, roles }: StaffIndexProps) {
    const loading = useNavigationLoading();
    const [credentials, clearCredentials] =
        useFlashData<TemporaryPasswordFlash>('temporaryPassword');

    const [formOpen, setFormOpen] = useState(false);
    // Naik setiap kali form dibuka agar isi form selalu mulai dari data terbaru.
    const [formKey, setFormKey] = useState(0);
    const [editing, setEditing] = useState<StaffMember | null>(null);
    const [pending, setPending] = useState<{
        action: ConfirmableAction;
        staff: StaffMember;
    } | null>(null);
    const [processing, setProcessing] = useState(false);

    const openForm = (member: StaffMember | null) => {
        setEditing(member);
        setFormKey((key) => key + 1);
        setFormOpen(true);
    };

    const handleAction = (action: StaffAction, member: StaffMember) => {
        if (action === 'edit') {
            openForm(member);

            return;
        }

        setPending({ action, staff: member });
    };

    const confirmPending = () => {
        if (pending === null) {
            return;
        }

        router.visit(confirmations[pending.action].route(pending.staff.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setPending(null);
            },
        });
    };

    const confirmation = pending ? confirmations[pending.action] : null;

    return (
        <>
            <Head title="Kelola Petugas" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Kelola Petugas"
                        description="Akun Pengelola dan Admin yang dapat memakai aplikasi."
                    />
                    <Button
                        onClick={() => openForm(null)}
                        data-test="add-staff-button"
                    >
                        <UserPlus aria-hidden="true" />
                        Tambah Petugas
                    </Button>
                </div>

                <StaffFilters filters={filters} roles={roles} />

                <Card className="relative py-0" aria-busy={loading}>
                    <CardContent
                        className={cn(
                            'px-0 transition-opacity',
                            loading && 'opacity-60',
                        )}
                    >
                        {staff.data.length > 0 ? (
                            <StaffTable
                                staff={staff.data}
                                onAction={handleAction}
                            />
                        ) : hasActiveFilters(filters) ? (
                            <EmptyState
                                icon={SearchX}
                                title="Tidak ada petugas yang cocok"
                                description="Coba ubah kata kunci pencarian atau filter."
                            />
                        ) : (
                            <EmptyState
                                icon={Users}
                                title="Belum ada akun petugas"
                                description="Tambahkan akun untuk petugas GA yang akan memakai aplikasi."
                            />
                        )}
                    </CardContent>
                    {loading && (
                        <div className="absolute inset-0 flex items-center justify-center">
                            <Spinner />
                        </div>
                    )}
                </Card>

                <PaginationNav pagination={staff} />
            </div>

            <StaffFormDialog
                key={formKey}
                open={formOpen}
                onOpenChange={setFormOpen}
                staff={editing}
                roles={roles}
            />

            {confirmation && pending && (
                <ConfirmDialog
                    open
                    onOpenChange={(open) => !open && setPending(null)}
                    title={confirmation.title}
                    description={confirmation.description(pending.staff)}
                    confirmLabel={confirmation.confirmLabel}
                    destructive={confirmation.destructive}
                    processing={processing}
                    onConfirm={confirmPending}
                />
            )}

            <TemporaryPasswordDialog
                credentials={credentials}
                onClose={clearCredentials}
            />
        </>
    );
}

StaffIndex.layout = {
    breadcrumbs: [
        {
            title: 'Kelola Petugas',
            href: index(),
        },
    ],
};

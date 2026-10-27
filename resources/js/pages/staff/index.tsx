import { Head, router } from '@inertiajs/react';
import { UserPlus, Users } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTableCard } from '@/components/data-table/data-table-card';
import { PageHeader } from '@/components/page-header';
import type { StaffAction } from '@/components/staff/staff-actions-menu';
import { StaffActionsMenu } from '@/components/staff/staff-actions-menu';
import { useStaffColumns } from '@/components/staff/staff-columns';
import { StaffFormDialog } from '@/components/staff/staff-form-dialog';
import { TemporaryPasswordDialog } from '@/components/staff/temporary-password-dialog';
import { Button } from '@/components/ui/button';
import { useFlashData } from '@/hooks/use-flash-data';
import { useTableQuery } from '@/hooks/use-table-query';
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

export default function StaffIndex({
    staff,
    query,
    roles,
    statuses,
}: StaffIndexProps) {
    const table = useTableQuery({
        url: index.url(),
        query,
        only: ['staff', 'query'],
    });
    const columns = useStaffColumns();
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

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Kelola Petugas"
                    description="Kelola akun petugas yang dapat mengakses sistem beserta peran dan status aksesnya."
                    actions={
                        <Button
                            onClick={() => openForm(null)}
                            data-test="add-staff-button"
                        >
                            <UserPlus aria-hidden="true" />
                            Tambah Petugas
                        </Button>
                    }
                />

                <DataTableCard
                    title="Daftar Petugas"
                    table={table}
                    data={staff}
                    columns={columns}
                    rowKey={(member) => member.id}
                    rowActions={(member) => (
                        <StaffActionsMenu
                            staff={member}
                            onAction={handleAction}
                        />
                    )}
                    searchPlaceholder="Cari nama atau email..."
                    filterGroups={[
                        { key: 'role', label: 'Peran', options: roles },
                        { key: 'status', label: 'Status', options: statuses },
                    ]}
                    emptyState={{
                        icon: Users,
                        title: 'Belum ada akun petugas',
                        description:
                            'Tambahkan akun untuk petugas GA yang akan memakai aplikasi.',
                        action: (
                            <Button onClick={() => openForm(null)}>
                                <UserPlus aria-hidden="true" />
                                Tambah Petugas
                            </Button>
                        ),
                    }}
                />
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

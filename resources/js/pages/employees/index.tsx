import { Head } from '@inertiajs/react';
import { UserPlus, Users } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTableCard } from '@/components/data-table/data-table-card';
import type { EmployeeAction } from '@/components/employees/employee-actions-menu';
import { EmployeeActionsMenu } from '@/components/employees/employee-actions-menu';
import { useEmployeeColumns } from '@/components/employees/employee-columns';
import { EmployeeFormDialog } from '@/components/employees/employee-form-dialog';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useConfirmAction } from '@/hooks/use-confirm-action';
import { useTableQuery } from '@/hooks/use-table-query';
import { activate, deactivate, index } from '@/routes/employees';
import type { Employee, EmployeeIndexProps } from '@/types';

export default function EmployeeIndex({
    employees,
    query,
    divisions,
    statuses,
}: EmployeeIndexProps) {
    const table = useTableQuery({
        url: index.url(),
        query,
        only: ['employees', 'query'],
    });
    const columns = useEmployeeColumns();
    const confirmation = useConfirmAction();

    const [formOpen, setFormOpen] = useState(false);
    // Naik setiap kali form dibuka agar isi form selalu mulai dari data terbaru.
    const [formKey, setFormKey] = useState(0);
    const [editing, setEditing] = useState<Employee | null>(null);

    const openForm = (employee: Employee | null) => {
        setEditing(employee);
        setFormKey((key) => key + 1);
        setFormOpen(true);
    };

    const handleAction = (action: EmployeeAction, employee: Employee) => {
        if (action === 'edit') {
            openForm(employee);

            return;
        }

        confirmation.request(
            employee.is_active
                ? {
                      title: 'Nonaktifkan pegawai?',
                      description: `${employee.name} tidak dapat dipilih untuk jadwal baru. Riwayat peminjamannya tetap tersimpan, dan data dapat diaktifkan kembali.`,
                      confirmLabel: 'Nonaktifkan',
                      destructive: true,
                      route: deactivate(employee.id),
                  }
                : {
                      title: 'Aktifkan kembali pegawai?',
                      description: `${employee.name} dapat dipilih lagi untuk jadwal baru.`,
                      confirmLabel: 'Aktifkan',
                      route: activate(employee.id),
                  },
        );
    };

    return (
        <>
            <Head title="Data Pegawai" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Data Pegawai"
                    description="Kelola data pegawai yang dapat dicatat sebagai peminjam mobil dinas beserta kontaknya."
                    actions={
                        <Button
                            onClick={() => openForm(null)}
                            data-test="add-employee-button"
                        >
                            <UserPlus aria-hidden="true" />
                            Tambah Pegawai
                        </Button>
                    }
                />

                <DataTableCard
                    title="Daftar Pegawai"
                    table={table}
                    data={employees}
                    columns={columns}
                    rowKey={(employee) => employee.id}
                    rowActions={(employee) => (
                        <EmployeeActionsMenu
                            employee={employee}
                            onAction={handleAction}
                        />
                    )}
                    searchPlaceholder="Cari nama, no HP, atau divisi..."
                    filterGroups={[
                        {
                            key: 'division',
                            label: 'Divisi',
                            options: divisions,
                        },
                        { key: 'status', label: 'Status', options: statuses },
                    ]}
                    emptyState={{
                        icon: Users,
                        title: 'Belum ada data pegawai',
                        description:
                            'Tambahkan pegawai agar dapat dipilih saat menjadwalkan peminjaman.',
                        action: (
                            <Button onClick={() => openForm(null)}>
                                <UserPlus aria-hidden="true" />
                                Tambah Pegawai
                            </Button>
                        ),
                    }}
                />
            </div>

            <EmployeeFormDialog
                key={formKey}
                open={formOpen}
                onOpenChange={setFormOpen}
                employee={editing}
                divisions={divisions}
            />

            <ConfirmDialog {...confirmation.dialogProps} />
        </>
    );
}

EmployeeIndex.layout = {
    breadcrumbs: [{ title: 'Data Pegawai', href: index() }],
};

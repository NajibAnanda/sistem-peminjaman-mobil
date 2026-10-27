import { Head } from '@inertiajs/react';
import { CarFront, Plus } from 'lucide-react';
import { useState } from 'react';
import type { CarAction } from '@/components/cars/car-actions-menu';
import { CarActionsMenu } from '@/components/cars/car-actions-menu';
import { useCarColumns } from '@/components/cars/car-columns';
import { carActivationConfirmation } from '@/components/cars/car-confirmations';
import { CarFormDialog } from '@/components/cars/car-form-dialog';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTableCard } from '@/components/data-table/data-table-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useConfirmAction } from '@/hooks/use-confirm-action';
import { useTableQuery } from '@/hooks/use-table-query';
import { index } from '@/routes/cars';
import type { Car, CarIndexProps } from '@/types';

export default function CarIndex({ cars, query, statuses }: CarIndexProps) {
    const table = useTableQuery({
        url: index.url(),
        query,
        only: ['cars', 'query'],
    });
    const columns = useCarColumns();
    const confirmation = useConfirmAction();

    const [formOpen, setFormOpen] = useState(false);
    // Naik setiap kali form dibuka agar isi form selalu mulai dari data terbaru.
    const [formKey, setFormKey] = useState(0);
    const [editing, setEditing] = useState<Car | null>(null);

    const openForm = (car: Car | null) => {
        setEditing(car);
        setFormKey((key) => key + 1);
        setFormOpen(true);
    };

    const handleAction = (action: CarAction, car: Car) => {
        if (action === 'edit') {
            openForm(car);

            return;
        }

        confirmation.request(carActivationConfirmation(car));
    };

    return (
        <>
            <Head title="Data Mobil" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Data Mobil"
                    description="Daftar armada mobil dinas beserta status ketersediaan dan kondisi operasionalnya."
                    actions={
                        <Button
                            onClick={() => openForm(null)}
                            data-test="add-car-button"
                        >
                            <Plus aria-hidden="true" />
                            Tambah Mobil
                        </Button>
                    }
                />

                <DataTableCard
                    title="Daftar Mobil"
                    table={table}
                    data={cars}
                    columns={columns}
                    rowKey={(car) => car.id}
                    rowActions={(car) => (
                        <CarActionsMenu car={car} onAction={handleAction} />
                    )}
                    searchPlaceholder="Cari plat, merk, atau tipe..."
                    filterGroups={[
                        { key: 'status', label: 'Status', options: statuses },
                    ]}
                    emptyState={{
                        icon: CarFront,
                        title: 'Belum ada data mobil',
                        description:
                            'Tambahkan mobil dinas agar dapat dijadwalkan untuk peminjaman.',
                        action: (
                            <Button onClick={() => openForm(null)}>
                                <Plus aria-hidden="true" />
                                Tambah Mobil
                            </Button>
                        ),
                    }}
                />
            </div>

            <CarFormDialog
                key={formKey}
                open={formOpen}
                onOpenChange={setFormOpen}
                car={editing}
            />

            <ConfirmDialog {...confirmation.dialogProps} />
        </>
    );
}

CarIndex.layout = {
    breadcrumbs: [{ title: 'Data Mobil', href: index() }],
};

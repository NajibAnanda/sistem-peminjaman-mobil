import { Head } from '@inertiajs/react';
import { FileSearch, ScrollText } from 'lucide-react';
import { useState } from 'react';
import { useActivityColumns } from '@/components/activity-log/activity-columns';
import { ActivityDetailDialog } from '@/components/activity-log/activity-detail-dialog';
import { DataTableCard } from '@/components/data-table/data-table-card';
import { RowActions } from '@/components/data-table/row-actions';
import { PageHeader } from '@/components/page-header';
import { useTableQuery } from '@/hooks/use-table-query';
import { index } from '@/routes/activity-log';
import type { ActivityEntry, ActivityLogIndexProps } from '@/types';

export default function ActivityLogIndex({
    activities,
    query,
    filterOptions,
}: ActivityLogIndexProps) {
    const table = useTableQuery({
        url: index.url(),
        query,
        only: ['activities', 'query'],
    });
    const columns = useActivityColumns();
    const [selected, setSelected] = useState<ActivityEntry | null>(null);

    return (
        <>
            <Head title="Log Aktivitas" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Log Aktivitas"
                    description="Pantau siapa mengubah data apa dan kapan. Catatan log tidak dapat diubah atau dihapus."
                />

                <DataTableCard
                    title="Daftar Aktivitas"
                    table={table}
                    data={activities}
                    columns={columns}
                    rowKey={(activity) => activity.id}
                    rowActions={(activity) => (
                        <RowActions
                            label={`Aksi untuk: ${activity.description}`}
                            actions={[
                                {
                                    label: 'Lihat rincian',
                                    icon: FileSearch,
                                    onSelect: () => setSelected(activity),
                                },
                            ]}
                        />
                    )}
                    searchPlaceholder="Cari aktivitas…"
                    filterGroups={[
                        {
                            key: 'type',
                            label: 'Jenis data',
                            options: filterOptions.type,
                        },
                        {
                            key: 'staff',
                            label: 'Petugas',
                            options: filterOptions.staff,
                            submenu: true,
                        },
                    ]}
                    emptyState={{
                        icon: ScrollText,
                        title: 'Belum ada aktivitas',
                        description:
                            'Setiap perubahan data oleh petugas akan tercatat di sini.',
                    }}
                />
            </div>

            <ActivityDetailDialog
                activity={selected}
                onOpenChange={(open) => !open && setSelected(null)}
            />
        </>
    );
}

ActivityLogIndex.layout = {
    breadcrumbs: [{ title: 'Log Aktivitas', href: index() }],
};

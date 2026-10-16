import { Head } from '@inertiajs/react';
import { FileSearch, ScrollText } from 'lucide-react';
import { useState } from 'react';
import { useActivityColumns } from '@/components/activity-log/activity-columns';
import { ActivityDetailDialog } from '@/components/activity-log/activity-detail-dialog';
import { DataTableCard } from '@/components/data-table/data-table-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
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
                    description="Catatan siapa mengubah apa dan kapan. Log tidak dapat diubah atau dihapus."
                />

                <DataTableCard
                    title="Daftar Aktivitas"
                    description="Terbaru di atas."
                    table={table}
                    data={activities}
                    columns={columns}
                    rowKey={(activity) => activity.id}
                    rowActions={(activity) => (
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            onClick={() => setSelected(activity)}
                            aria-label={`Lihat rincian: ${activity.description}`}
                        >
                            <FileSearch aria-hidden="true" />
                        </Button>
                    )}
                    searchPlaceholder="Cari aktivitas..."
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

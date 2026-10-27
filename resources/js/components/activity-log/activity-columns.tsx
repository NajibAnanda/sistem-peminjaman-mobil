import { useMemo } from 'react';
import { TruncatedText } from '@/components/data-table/truncated-text';
import type { DataTableColumn } from '@/components/data-table/types';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/hooks/use-date-format';
import type { ActivityEntry } from '@/types';

/**
 * Rincian perubahan (nilai sebelum/sesudah) dibuka lewat aksi "Lihat
 * rincian"; tabel hanya menampilkan jumlahnya agar satu kolom = satu data.
 */
export function useActivityColumns(): DataTableColumn<ActivityEntry>[] {
    const { formatDateTime } = useDateFormat();

    return useMemo(
        () => [
            {
                id: 'time',
                header: 'Waktu',
                skeleton: 'date',
                cell: (activity) => (
                    <span className="tabular-nums">
                        {formatDateTime(activity.created_at)}
                    </span>
                ),
            },
            {
                id: 'causer',
                header: 'Petugas',
                cell: (activity) => (
                    <TruncatedText
                        text={activity.causer_name}
                        className="max-w-44 font-medium"
                    />
                ),
            },
            {
                id: 'type',
                header: 'Jenis data',
                skeleton: 'badge',
                cell: (activity) => (
                    <Badge variant="secondary">{activity.type_label}</Badge>
                ),
            },
            {
                id: 'subject',
                header: 'Data',
                cell: (activity) =>
                    activity.subject_label ? (
                        <TruncatedText
                            text={activity.subject_label}
                            className="max-w-48"
                        />
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    ),
            },
            {
                id: 'description',
                header: 'Aktivitas',
                cell: (activity) => (
                    <TruncatedText
                        text={activity.description}
                        className="max-w-80"
                    />
                ),
            },
            {
                id: 'changes',
                header: 'Perubahan',
                align: 'right',
                cell: (activity) =>
                    activity.changes.length > 0 ? (
                        <span className="tabular-nums">
                            {activity.changes.length}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    ),
            },
        ],
        [formatDateTime],
    );
}

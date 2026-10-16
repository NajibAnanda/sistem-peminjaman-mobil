import type { DataTableColumn } from '@/components/data-table/types';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/hooks/use-date-format';
import type { ActivityEntry } from '@/types';

/** Jumlah perubahan yang diringkas di tabel; selebihnya di dialog rincian. */
const PREVIEW_CHANGES = 2;

export function useActivityColumns(): DataTableColumn<ActivityEntry>[] {
    const { formatDateTime } = useDateFormat();

    return [
        {
            id: 'time',
            header: 'Waktu',
            cell: (activity) => (
                <span className="whitespace-nowrap tabular-nums">
                    {formatDateTime(activity.created_at)}
                </span>
            ),
        },
        {
            id: 'causer',
            header: 'Petugas',
            cell: (activity) => (
                <span className="font-medium">{activity.causer_name}</span>
            ),
        },
        {
            id: 'type',
            header: 'Jenis data',
            cell: (activity) => (
                <div className="flex flex-col items-start gap-1">
                    <Badge variant="secondary">{activity.type_label}</Badge>
                    {activity.subject_label && (
                        <span className="text-xs text-muted-foreground">
                            {activity.subject_label}
                        </span>
                    )}
                </div>
            ),
        },
        {
            id: 'description',
            header: 'Aktivitas',
            cell: (activity) => {
                const preview = activity.changes.slice(0, PREVIEW_CHANGES);
                const rest = activity.changes.length - preview.length;

                return (
                    <div className="grid max-w-md gap-1 whitespace-normal">
                        <span>{activity.description}</span>
                        {preview.map((change) => (
                            <span
                                key={change.field}
                                className="truncate text-xs text-muted-foreground"
                            >
                                {change.label}:{' '}
                                {change.old !== null
                                    ? `${change.old} → ${change.new ?? '—'}`
                                    : (change.new ?? '—')}
                            </span>
                        ))}
                        {rest > 0 && (
                            <span className="text-xs text-muted-foreground">
                                dan {rest} perubahan lain
                            </span>
                        )}
                    </div>
                );
            },
        },
    ];
}

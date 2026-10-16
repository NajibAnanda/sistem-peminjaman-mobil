import { ActivityChangeList } from '@/components/activity-log/activity-change-list';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useDateFormat } from '@/hooks/use-date-format';
import type { ActivityEntry } from '@/types';

type Props = {
    activity: ActivityEntry | null;
    onOpenChange: (open: boolean) => void;
};

/** Rincian satu log: semua perubahan nilai dan keterangan tambahan. */
export function ActivityDetailDialog({ activity, onOpenChange }: Props) {
    const { formatDateTime } = useDateFormat();

    return (
        <Dialog open={activity !== null} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                {activity && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{activity.description}</DialogTitle>
                            <DialogDescription>
                                {formatDateTime(activity.created_at)} oleh{' '}
                                {activity.causer_name} · {activity.type_label}
                                {activity.subject_label &&
                                    ` · ${activity.subject_label}`}
                            </DialogDescription>
                        </DialogHeader>

                        {activity.changes.length > 0 && (
                            <section className="grid gap-2">
                                <h3 className="text-sm font-medium">
                                    Perubahan
                                </h3>
                                <ActivityChangeList
                                    changes={activity.changes}
                                />
                            </section>
                        )}

                        {activity.details.length > 0 && (
                            <section className="grid gap-2">
                                <h3 className="text-sm font-medium">
                                    Keterangan
                                </h3>
                                <dl className="grid gap-2 text-sm">
                                    {activity.details.map((detail) => (
                                        <div
                                            key={detail.label}
                                            className="grid gap-0.5 sm:grid-cols-[10rem_1fr] sm:gap-3"
                                        >
                                            <dt className="text-muted-foreground">
                                                {detail.label}
                                            </dt>
                                            <dd className="break-words whitespace-pre-line">
                                                {detail.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </section>
                        )}

                        {activity.changes.length === 0 &&
                            activity.details.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Tidak ada rincian perubahan untuk aktivitas
                                    ini.
                                </p>
                            )}
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

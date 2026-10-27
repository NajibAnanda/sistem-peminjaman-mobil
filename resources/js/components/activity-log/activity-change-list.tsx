import { ArrowRight } from 'lucide-react';
import type { ActivityChange } from '@/types';

type Props = {
    changes: ActivityChange[];
};

const EMPTY = '—';

/**
 * Perubahan nilai dalam bahasa sehari-hari: "Rencana waktu kembali:
 * 20 Okt 2026, 14.00 → 20 Okt 2026, 17.00". Data baru (tanpa nilai lama)
 * cukup menampilkan nilainya.
 */
export function ActivityChangeList({ changes }: Props) {
    return (
        <dl className="grid gap-2 text-sm">
            {changes.map((change) => (
                <div
                    key={change.field}
                    className="grid gap-0.5 sm:grid-cols-[10rem_1fr] sm:gap-3"
                >
                    <dt className="text-muted-foreground">{change.label}</dt>
                    <dd className="flex min-w-0 flex-wrap items-center gap-1.5 wrap-break-word">
                        {change.old !== null || change.new === null ? (
                            <>
                                <span className="text-muted-foreground line-through decoration-muted-foreground/50">
                                    <span className="sr-only">Semula: </span>
                                    {change.old ?? EMPTY}
                                </span>
                                <ArrowRight
                                    className="size-3.5 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <span className="font-medium">
                                    <span className="sr-only">
                                        , diubah menjadi:{' '}
                                    </span>
                                    {change.new ?? EMPTY}
                                </span>
                            </>
                        ) : (
                            <span className="font-medium">{change.new}</span>
                        )}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

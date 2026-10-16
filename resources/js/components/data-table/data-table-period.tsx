import { usePage } from '@inertiajs/react';
import { CalendarRange, RotateCcw } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { TablePeriod, UseTableQueryReturn } from '@/hooks/use-table-query';
import {
    formatDateOnly,
    monthRange,
    shiftDate,
    todayDate,
} from '@/lib/datetime';

type Props = {
    table: UseTableQueryReturn;
};

const LAST_WEEK_DAYS = 6;
const LAST_MONTH_DAYS = 29;

/**
 * Filter periode tabel (Riwayat, Log Aktivitas). Tanggal diisi lewat input
 * bawaan browser, atau pilihan cepat. Rentang disimpan di query string
 * (?from=&to=) sehingga bisa dibagikan dan ikut terbawa ke ekspor.
 */
export function DataTablePeriod({ table }: Props) {
    const { timezone } = usePage().props;
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<TablePeriod>(table.period);
    const active = table.period.from !== '' || table.period.to !== '';
    const today = todayDate(timezone);

    const presets: { label: string; period: TablePeriod }[] = [
        { label: 'Hari ini', period: { from: today, to: today } },
        {
            label: '7 hari terakhir',
            period: { from: shiftDate(today, -LAST_WEEK_DAYS), to: today },
        },
        {
            label: '30 hari terakhir',
            period: { from: shiftDate(today, -LAST_MONTH_DAYS), to: today },
        },
        { label: 'Bulan ini', period: monthRange(today) },
        { label: 'Bulan lalu', period: monthRange(today, -1) },
    ];

    const apply = (period: TablePeriod) => {
        table.setPeriod(period);
        setOpen(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        apply(draft);
    };

    const label = (date: string) => formatDateOnly(date, timezone);
    const summary = active
        ? table.period.from !== '' && table.period.to !== ''
            ? table.period.from === table.period.to
                ? label(table.period.from)
                : `${label(table.period.from)} – ${label(table.period.to)}`
            : table.period.from !== ''
              ? `Sejak ${label(table.period.from)}`
              : `Sampai ${label(table.period.to)}`
        : 'Periode';

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                // Isi form selalu mulai dari periode yang sedang berlaku.
                if (next) {
                    setDraft(table.period);
                }

                setOpen(next);
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    variant={active ? 'secondary' : 'outline'}
                    className="max-w-full"
                    data-test="table-period-button"
                >
                    <CalendarRange aria-hidden="true" />
                    <span className="truncate">{summary}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-80">
                <form onSubmit={submit} className="grid gap-3">
                    <div className="grid grid-cols-2 gap-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="period-from">Dari</Label>
                            <Input
                                id="period-from"
                                type="date"
                                value={draft.from}
                                max={draft.to || undefined}
                                onChange={(e) =>
                                    setDraft({ ...draft, from: e.target.value })
                                }
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="period-to">Sampai</Label>
                            <Input
                                id="period-to"
                                type="date"
                                value={draft.to}
                                min={draft.from || undefined}
                                onChange={(e) =>
                                    setDraft({ ...draft, to: e.target.value })
                                }
                            />
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-1.5">
                        {presets.map((preset) => (
                            <Button
                                key={preset.label}
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => apply(preset.period)}
                            >
                                {preset.label}
                            </Button>
                        ))}
                    </div>

                    <div className="flex justify-between gap-2 border-t pt-3">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            disabled={!active}
                            onClick={() => apply({ from: '', to: '' })}
                        >
                            <RotateCcw aria-hidden="true" />
                            Semua periode
                        </Button>
                        <Button type="submit" size="sm">
                            Terapkan
                        </Button>
                    </div>
                </form>
            </PopoverContent>
        </Popover>
    );
}

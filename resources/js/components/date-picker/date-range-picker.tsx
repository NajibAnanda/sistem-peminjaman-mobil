import { usePage } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import type { ReactElement } from 'react';
import { useState } from 'react';
import type { DateRange as CalendarRange } from 'react-day-picker';
import { CalendarPanel } from '@/components/date-picker/calendar-panel';
import {
    PickerTrigger,
    pickerPopoverProps,
} from '@/components/date-picker/picker-trigger';
import {
    formatDateValue,
    MONTHS_SHORT,
    parseDate,
    toDateValue,
} from '@/components/date-picker/utils';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { todayDate } from '@/lib/datetime';
import { cn } from '@/lib/utils';

/** Rentang tanggal "2026-10-17"; string kosong = tidak dibatasi. */
export type DateRangeValue = { from: string; to: string };

export type DateRangePreset = { label: string; range: DateRangeValue };

const EMPTY: DateRangeValue = { from: '', to: '' };

type Props = {
    value: DateRangeValue;
    onChange: (value: DateRangeValue) => void;
    /** Pilihan cepat, mis. "7 hari terakhir"; langsung diterapkan. */
    presets?: DateRangePreset[];
    /** Label tombol pengosong rentang, mis. "Semua periode". */
    /** Label tombol pengosong rentang; tanpa label = rentang wajib (tanpa tombol). */
    clearLabel?: string;
    /** Pemicu khusus (mis. tombol Periode di tabel); bawaan bergaya Input. */
    trigger?: ReactElement;
    placeholder?: string;
    align?: 'start' | 'end';
};

/**
 * Format rentang tanggal cerdas & ringkas:
 * - Tanggal sama: "17 Okt 2026"
 * - Bulan & tahun sama: "1–31 Okt 2026"
 * - Bulan beda, tahun sama: "15 Sep – 20 Okt 2026"
 * - Tahun beda: "15 Des 2025 – 10 Jan 2026"
 * - Batas terbuka: "Sejak 17 Okt 2026" / "Sampai 20 Okt 2026"
 */
export function formatDateRange(value: DateRangeValue): string | null {
    const { from, to } = value;

    if (from !== '' && to !== '') {
        if (from === to) {
            return formatDateValue(from);
        }

        const [yFrom, mFrom, dFrom] = from.split('-').map(Number);
        const [yTo, mTo, dTo] = to.split('-').map(Number);

        if (yFrom && mFrom && dFrom && yTo && mTo && dTo) {
            const monthFrom = MONTHS_SHORT[mFrom - 1];
            const monthTo = MONTHS_SHORT[mTo - 1];

            if (yFrom === yTo) {
                if (mFrom === mTo) {
                    return `${dFrom}–${dTo} ${monthFrom} ${yFrom}`;
                }

                return `${dFrom} ${monthFrom} – ${dTo} ${monthTo} ${yFrom}`;
            }

            return `${dFrom} ${monthFrom} ${yFrom} – ${dTo} ${monthTo} ${yTo}`;
        }

        return `${formatDateValue(from)} – ${formatDateValue(to)}`;
    }

    if (from !== '') {
        return `Sejak ${formatDateValue(from)}`;
    }

    return to !== '' ? `Sampai ${formatDateValue(to)}` : null;
}

/**
 * Pemilih rentang tanggal (filter periode): klik tanggal awal lalu tanggal
 * akhir, atau pilih rentang cepat, kemudian "Terapkan".
 */
export function DateRangePicker({
    value,
    onChange,
    presets = [],
    clearLabel,
    trigger,
    placeholder = 'Pilih rentang tanggal',
    align = 'start',
}: Props) {
    const { timezone } = usePage().props;
    const [open, setOpen] = useState(false);
    const today = parseDate(todayDate(timezone)) ?? new Date();
    const [draft, setDraft] = useState<CalendarRange | undefined>();
    const [month, setMonth] = useState(today);
    const active = value.from !== '' || value.to !== '';

    const apply = (next: DateRangeValue) => {
        onChange(next);
        setOpen(false);
    };

    const applyDraft = () => {
        if (!draft?.from) {
            return;
        }

        const from = toDateValue(draft.from);

        // Satu tanggal saja berarti rentang satu hari.
        apply({ from, to: draft.to ? toDateValue(draft.to) : from });
    };

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                // Selalu mulai dari rentang yang sedang berlaku.
                if (next) {
                    const from = parseDate(value.from);

                    setDraft(
                        from ? { from, to: parseDate(value.to) } : undefined,
                    );
                    setMonth(from ?? today);
                }

                setOpen(next);
            }}
        >
            <PopoverTrigger asChild>
                {trigger ?? (
                    <PickerTrigger
                        text={formatDateRange(value)}
                        placeholder={placeholder}
                    />
                )}
            </PopoverTrigger>
            <PopoverContent
                align={align}
                {...pickerPopoverProps}
                data-test="date-range-picker"
            >
                <div className="flex flex-col divide-y divide-border sm:flex-row sm:divide-x sm:divide-y-0">
                    {presets.length > 0 && (
                        <div className="flex w-full flex-col gap-1 pb-2 sm:w-36 sm:pr-3 sm:pb-0">
                            <span className="px-2 py-1 text-xs font-semibold text-muted-foreground">
                                Pilihan Cepat
                            </span>
                            <div className="grid grid-cols-2 gap-1 sm:grid-cols-1">
                                {presets.map((preset) => (
                                    <Button
                                        key={preset.label}
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="justify-start text-xs font-normal"
                                        onClick={() => apply(preset.range)}
                                    >
                                        {preset.label}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}

                    <div
                        className={cn(
                            'grid gap-2',
                            presets.length > 0 && 'pt-2 sm:pt-0 sm:pl-3',
                        )}
                    >
                        <CalendarPanel
                            mode="range"
                            selected={draft}
                            onSelect={setDraft}
                            month={month}
                            onMonthChange={setMonth}
                            today={today}
                        />

                        <p
                            className="text-center text-xs text-muted-foreground tabular-nums"
                            aria-live="polite"
                        >
                            {draft?.from
                                ? formatDateRange({
                                      from: toDateValue(draft.from),
                                      to: toDateValue(draft.to ?? draft.from),
                                  })
                                : 'Pilih tanggal awal, lalu tanggal akhir.'}
                        </p>

                        <div className="flex items-center justify-end gap-2 border-t border-border pt-2">
                            {clearLabel && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="mr-auto"
                                    disabled={!active}
                                    onClick={() => apply(EMPTY)}
                                >
                                    <RotateCcw aria-hidden="true" />
                                    {clearLabel}
                                </Button>
                            )}
                            <Button
                                type="button"
                                size="sm"
                                disabled={!draft?.from}
                                onClick={applyDraft}
                                data-test="date-picker-apply"
                            >
                                Terapkan
                            </Button>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

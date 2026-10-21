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

/** "17 Okt 2026 – 20 Okt 2026", "Sejak 17 Okt 2026", dst. */
export function formatDateRange(value: DateRangeValue): string | null {
    const { from, to } = value;

    if (from !== '' && to !== '') {
        return from === to
            ? formatDateValue(from)
            : `${formatDateValue(from)} – ${formatDateValue(to)}`;
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

                {presets.length > 0 && (
                    <div className="flex w-64 flex-wrap gap-1 border-t pt-2.5">
                        {presets.map((preset) => (
                            <Button
                                key={preset.label}
                                type="button"
                                variant="outline"
                                size="xs"
                                onClick={() => apply(preset.range)}
                            >
                                {preset.label}
                            </Button>
                        ))}
                    </div>
                )}

                <div className="flex justify-end gap-2 border-t pt-2.5">
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
            </PopoverContent>
        </Popover>
    );
}

import { usePage } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { useMemo, useState } from 'react';
import { CalendarPanel } from '@/components/date-picker/calendar-panel';
import {
    ClearButton,
    PickerTrigger,
    pickerPopoverProps,
} from '@/components/date-picker/picker-trigger';
import {
    ceilToStep,
    formatDateTimeValue,
    parseDate,
    parseTime,
    toDateTimeValue,
    toDateValue,
} from '@/components/date-picker/utils';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toDateTimeLocal } from '@/lib/datetime';

const HOURS = Array.from({ length: 24 }, (_, hour) => hour);
const pad = (value: number) => String(value).padStart(2, '0');

type Props = {
    id?: string;
    /** "2026-10-17T14:30" di zona waktu aplikasi, atau '' bila kosong. */
    value: string;
    onChange: (value: string) => void;
    /** Waktu paling awal yang boleh dipilih (format sama dengan value). */
    min?: string;
    placeholder?: string;
    invalid?: boolean;
    disabled?: boolean;
    /** Field opsional: tampilkan tombol hapus (×). */
    clearable?: boolean;
    'aria-describedby'?: string;
};

type Draft = { date: string; hour: number; minute: number };

/**
 * Pemilih tanggal & jam: kalender (hari/bulan/tahun) lalu jam dan menit
 * (kelipatan dari config/fleet.php), diterapkan dengan tombol "Terapkan".
 * Validasi tetap di server; batas `min` hanya memandu pilihan.
 */
export function DateTimePicker({
    id,
    value,
    onChange,
    min,
    placeholder = 'Pilih tanggal & jam',
    invalid,
    disabled,
    clearable = false,
    'aria-describedby': describedBy,
}: Props) {
    const { timezone, minuteStep } = usePage().props;
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<Draft>({ date: '', hour: 0, minute: 0 });
    const [month, setMonth] = useState(new Date());

    const now = toDateTimeLocal(new Date(), timezone);
    const today = parseDate(now) ?? new Date();
    const minDate = min ? parseDate(min) : undefined;

    const startDraft = () => {
        // Field kosong: mulai dari jam terdekat berikutnya (atau batas awal).
        const initial =
            value !== ''
                ? value
                : ceilToStep(min && min > now ? min : now, minuteStep);
        const { hour, minute } = parseTime(initial);

        setDraft({
            date: value !== '' ? value.slice(0, 10) : '',
            hour,
            minute,
        });
        setMonth(parseDate(initial) ?? today);
    };

    const candidate =
        draft.date !== ''
            ? toDateTimeValue(draft.date, draft.hour, draft.minute)
            : '';
    // Nilai lama yang sudah lewat batas boleh diterapkan ulang tanpa diubah
    // (mis. edit jadwal Belum Diambil tanpa mengubah waktu mulai).
    const tooEarly =
        min !== undefined &&
        candidate !== '' &&
        candidate < min &&
        candidate !== value;
    const onMinDay = min !== undefined && draft.date === min.slice(0, 10);
    const minTime = min ? parseTime(min) : undefined;

    const minutes = useMemo(() => {
        const list = Array.from(
            { length: Math.ceil(60 / minuteStep) },
            (_, index) => index * minuteStep,
        );

        // Menit nilai lama yang bukan kelipatan tetap bisa dipilih.
        if (!list.includes(draft.minute)) {
            list.push(draft.minute);
            list.sort((a, b) => a - b);
        }

        return list;
    }, [minuteStep, draft.minute]);

    const selectDate = (date: Date) => {
        const next = { ...draft, date: toDateValue(date) };
        const time = toDateTimeValue(next.date, next.hour, next.minute);

        // Hari pertama yang diizinkan: majukan jam ke batas awal.
        if (min && time < min) {
            const earliest = parseTime(ceilToStep(min, minuteStep));

            next.hour = earliest.hour;
            next.minute = earliest.minute;
        }

        setDraft(next);
    };

    const apply = () => {
        onChange(candidate);
        setOpen(false);
    };

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                if (next) {
                    startDraft();
                }

                setOpen(next);
            }}
        >
            <div className="relative">
                <PopoverTrigger asChild>
                    <PickerTrigger
                        id={id}
                        text={value !== '' ? formatDateTimeValue(value) : null}
                        placeholder={placeholder}
                        invalid={invalid}
                        disabled={disabled}
                        clearable={clearable}
                        aria-describedby={describedBy}
                    />
                </PopoverTrigger>
                {clearable && value !== '' && (
                    <ClearButton
                        label="Hapus tanggal & jam"
                        onClick={() => onChange('')}
                        disabled={disabled}
                    />
                )}
            </div>
            <PopoverContent
                align="start"
                {...pickerPopoverProps}
                data-test="date-time-picker"
            >
                <CalendarPanel
                    mode="single"
                    selected={parseDate(draft.date)}
                    onSelect={selectDate}
                    month={month}
                    onMonthChange={setMonth}
                    today={today}
                    minDate={minDate}
                />

                <div className="flex items-center gap-1.5 border-t pt-2.5">
                    <Clock
                        className="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Select
                        value={String(draft.hour)}
                        onValueChange={(hour) =>
                            setDraft({ ...draft, hour: Number(hour) })
                        }
                    >
                        <SelectTrigger
                            size="sm"
                            aria-label="Jam"
                            className="w-16 tabular-nums"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="max-h-60">
                            {HOURS.map((hour) => (
                                <SelectItem
                                    key={hour}
                                    value={String(hour)}
                                    className="tabular-nums"
                                    disabled={
                                        onMinDay &&
                                        minTime !== undefined &&
                                        hour < minTime.hour
                                    }
                                >
                                    {pad(hour)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <span aria-hidden="true">:</span>
                    <Select
                        value={String(draft.minute)}
                        onValueChange={(minute) =>
                            setDraft({ ...draft, minute: Number(minute) })
                        }
                    >
                        <SelectTrigger
                            size="sm"
                            aria-label="Menit"
                            className="w-16 tabular-nums"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="max-h-60">
                            {minutes.map((minute) => (
                                <SelectItem
                                    key={minute}
                                    value={String(minute)}
                                    className="tabular-nums"
                                    disabled={
                                        onMinDay &&
                                        minTime !== undefined &&
                                        draft.hour === minTime.hour &&
                                        minute < minTime.minute
                                    }
                                >
                                    {pad(minute)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        type="button"
                        size="sm"
                        className="ml-auto"
                        disabled={candidate === '' || tooEarly}
                        onClick={apply}
                        data-test="date-picker-apply"
                    >
                        Terapkan
                    </Button>
                </div>
                {tooEarly && min && (
                    <p className="text-xs text-destructive">
                        Paling awal {formatDateTimeValue(min)}.
                    </p>
                )}
            </PopoverContent>
        </Popover>
    );
}

import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { CalendarPanel } from '@/components/date-picker/calendar-panel';
import {
    ClearButton,
    PickerTrigger,
    pickerPopoverProps,
} from '@/components/date-picker/picker-trigger';
import {
    formatDateValue,
    parseDate,
    toDateValue,
} from '@/components/date-picker/utils';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { todayDate } from '@/lib/datetime';

type Props = {
    id?: string;
    /** "2026-10-17" atau '' bila kosong. */
    value: string;
    onChange: (value: string) => void;
    /** Tanggal paling awal yang boleh dipilih ("2026-10-17"). */
    min?: string;
    placeholder?: string;
    invalid?: boolean;
    disabled?: boolean;
    /** Field opsional: tampilkan tombol hapus (×). */
    clearable?: boolean;
    'aria-describedby'?: string;
};

/** Pemilih satu tanggal; memilih tanggal langsung menutup kalender. */
export function DatePicker({
    id,
    value,
    onChange,
    min,
    placeholder = 'Pilih tanggal',
    invalid,
    disabled,
    clearable = false,
    'aria-describedby': describedBy,
}: Props) {
    const { timezone } = usePage().props;
    const [open, setOpen] = useState(false);
    const today = parseDate(todayDate(timezone)) ?? new Date();
    const [month, setMonth] = useState(today);

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                if (next) {
                    setMonth(parseDate(value) ?? today);
                }

                setOpen(next);
            }}
        >
            <div className="relative">
                <PopoverTrigger asChild>
                    <PickerTrigger
                        id={id}
                        text={value !== '' ? formatDateValue(value) : null}
                        placeholder={placeholder}
                        invalid={invalid}
                        disabled={disabled}
                        clearable={clearable}
                        aria-describedby={describedBy}
                    />
                </PopoverTrigger>
                {clearable && value !== '' && (
                    <ClearButton
                        label="Hapus tanggal"
                        onClick={() => onChange('')}
                        disabled={disabled}
                    />
                )}
            </div>
            <PopoverContent align="start" {...pickerPopoverProps}>
                <CalendarPanel
                    mode="single"
                    selected={parseDate(value)}
                    onSelect={(date) => {
                        onChange(toDateValue(date));
                        setOpen(false);
                    }}
                    month={month}
                    onMonthChange={setMonth}
                    today={today}
                    minDate={min ? parseDate(min) : undefined}
                />
            </PopoverContent>
        </Popover>
    );
}

import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { KeyboardEvent, ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import type { DateRange, Matcher } from 'react-day-picker';
import { id } from 'react-day-picker/locale/id';
import { MONTHS_LONG, MONTHS_SHORT } from '@/components/date-picker/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { cn } from '@/lib/utils';

type View = 'day' | 'month' | 'year';

const MONTH_COLUMNS = 4;
const YEAR_COLUMNS = 5;
const YEARS_PER_PAGE = 15;
// Tahun aktif berada di tengah halaman tahun yang pertama dibuka.
const YEARS_BEFORE_ACTIVE = Math.floor(YEARS_PER_PAGE / 2);

type SelectionProps =
    | {
          mode: 'single';
          selected: Date | undefined;
          onSelect: (date: Date) => void;
      }
    | {
          mode: 'range';
          selected: DateRange | undefined;
          onSelect: (range: DateRange) => void;
      };

type Props = SelectionProps & {
    /** Bulan yang sedang ditampilkan (tanggal 1). */
    month: Date;
    onMonthChange: (month: Date) => void;
    /** Hari ini di zona waktu aplikasi. */
    today: Date;
    /** Tanggal paling awal yang boleh dipilih. */
    minDate?: Date;
};

const startOfMonth = (year: number, month: number) => new Date(year, month, 1);

function isMultiDayRange(range: DateRange | undefined): boolean {
    return (
        range?.from !== undefined &&
        range.to !== undefined &&
        range.from.getTime() !== range.to.getTime()
    );
}

/**
 * Kalender dengan tiga tampilan: hari (bawaan), bulan (klik nama bulan), dan
 * tahun (klik tahun). Header di atas: [<] label [>]. Locale Indonesia,
 * minggu dimulai hari Senin.
 */
export function CalendarPanel(props: Props) {
    const { month, onMonthChange, today, minDate } = props;
    const [view, setView] = useState<View>('day');
    const [yearPageStart, setYearPageStart] = useState(
        month.getFullYear() - YEARS_BEFORE_ACTIVE,
    );
    const year = month.getFullYear();
    const minMonth = minDate
        ? startOfMonth(minDate.getFullYear(), minDate.getMonth())
        : undefined;
    const minYear = minDate?.getFullYear();

    const openYears = () => {
        setYearPageStart(year - YEARS_BEFORE_ACTIVE);
        setView('year');
    };

    const header = {
        day: {
            label: (
                <span className="flex items-center gap-0.5">
                    <HeaderButton
                        onClick={() => setView('month')}
                        label={`Pilih bulan, saat ini ${MONTHS_LONG[month.getMonth()]}`}
                    >
                        {MONTHS_LONG[month.getMonth()]}
                    </HeaderButton>
                    <HeaderButton
                        onClick={openYears}
                        label={`Pilih tahun, saat ini ${year}`}
                    >
                        {year}
                    </HeaderButton>
                </span>
            ),
            previous: {
                label: 'Bulan sebelumnya',
                disabled:
                    minMonth !== undefined &&
                    startOfMonth(year, month.getMonth()) <= minMonth,
                onClick: () =>
                    onMonthChange(startOfMonth(year, month.getMonth() - 1)),
            },
            next: {
                label: 'Bulan berikutnya',
                onClick: () =>
                    onMonthChange(startOfMonth(year, month.getMonth() + 1)),
            },
        },
        month: {
            label: (
                <HeaderButton
                    onClick={openYears}
                    label={`Pilih tahun, saat ini ${year}`}
                >
                    {year}
                </HeaderButton>
            ),
            previous: {
                label: 'Tahun sebelumnya',
                disabled: minYear !== undefined && year <= minYear,
                onClick: () =>
                    onMonthChange(startOfMonth(year - 1, month.getMonth())),
            },
            next: {
                label: 'Tahun berikutnya',
                onClick: () =>
                    onMonthChange(startOfMonth(year + 1, month.getMonth())),
            },
        },
        year: {
            label: (
                <span className="px-2 text-sm font-medium tabular-nums">
                    {yearPageStart} – {yearPageStart + YEARS_PER_PAGE - 1}
                </span>
            ),
            previous: {
                label: `${YEARS_PER_PAGE} tahun sebelumnya`,
                disabled: minYear !== undefined && yearPageStart <= minYear,
                onClick: () => setYearPageStart(yearPageStart - YEARS_PER_PAGE),
            },
            next: {
                label: `${YEARS_PER_PAGE} tahun berikutnya`,
                onClick: () => setYearPageStart(yearPageStart + YEARS_PER_PAGE),
            },
        },
    }[view];

    // Tanggal bulan lain tetap dirender (6 baris tetap) tetapi disembunyikan
    // dan dinonaktifkan, sehingga tidak bisa diklik maupun difokus.
    const outsideMonth = (date: Date) =>
        date.getMonth() !== month.getMonth() ||
        date.getFullYear() !== month.getFullYear();
    const disabled: Matcher[] = minDate
        ? [{ before: minDate }, outsideMonth]
        : [outsideMonth];

    return (
        <div className="grid w-64 gap-1.5" data-test="calendar-panel">
            <div className="flex items-center justify-between gap-1">
                <NavButton direction="previous" {...header.previous} />
                {header.label}
                <NavButton direction="next" {...header.next} />
            </div>

            {/* Tinggi tetap agar popover tidak berubah ukuran saat berganti tampilan. */}
            <div className="h-58">
                {view === 'day' &&
                    (props.mode === 'single' ? (
                        <Calendar
                            {...dayPickerProps}
                            mode="single"
                            required
                            selected={props.selected}
                            onSelect={props.onSelect}
                            month={month}
                            onMonthChange={onMonthChange}
                            today={today}
                            disabled={disabled}
                        />
                    ) : (
                        <Calendar
                            {...dayPickerProps}
                            mode="range"
                            required
                            selected={props.selected}
                            onSelect={(range, triggerDate) =>
                                // Rentang lebih dari satu hari yang sudah
                                // lengkap: klik berikutnya memulai rentang
                                // baru, bukan menggeser ujungnya. (Klik pertama
                                // menghasilkan from = to, lalu klik kedua
                                // menjadi tanggal akhir.)
                                props.onSelect(
                                    isMultiDayRange(props.selected)
                                        ? { from: triggerDate, to: triggerDate }
                                        : range,
                                )
                            }
                            month={month}
                            onMonthChange={onMonthChange}
                            today={today}
                            disabled={disabled}
                        />
                    ))}

                {view === 'month' && (
                    <ChoiceGrid
                        columns={MONTH_COLUMNS}
                        label={`Pilih bulan tahun ${year}`}
                        items={MONTHS_SHORT.map((name, index) => ({
                            key: index,
                            label: name,
                            ariaLabel: `${MONTHS_LONG[index]} ${year}`,
                            active: index === month.getMonth(),
                            current:
                                year === today.getFullYear() &&
                                index === today.getMonth(),
                            disabled:
                                minMonth !== undefined &&
                                startOfMonth(year, index) < minMonth,
                            onSelect: () => {
                                onMonthChange(startOfMonth(year, index));
                                setView('day');
                            },
                        }))}
                    />
                )}

                {view === 'year' && (
                    <ChoiceGrid
                        columns={YEAR_COLUMNS}
                        label="Pilih tahun"
                        items={Array.from(
                            { length: YEARS_PER_PAGE },
                            (_, index) => {
                                const value = yearPageStart + index;

                                return {
                                    key: value,
                                    label: String(value),
                                    ariaLabel: String(value),
                                    active: value === year,
                                    current: value === today.getFullYear(),
                                    disabled:
                                        minYear !== undefined &&
                                        value < minYear,
                                    onSelect: () => {
                                        onMonthChange(
                                            startOfMonth(
                                                value,
                                                month.getMonth(),
                                            ),
                                        );
                                        setView('month');
                                    },
                                };
                            },
                        )}
                    />
                )}
            </div>
        </div>
    );
}

// Navigasi bawaan disembunyikan; header di atas yang mengatur bulan. Enam
// minggu selalu tampil agar tinggi kalender tetap.
const dayPickerProps = {
    locale: id,
    weekStartsOn: 1,
    fixedWeeks: true,
    hideNavigation: true,
    // Hari Minggu merah seperti kalender pada umumnya; tanggal terpilih tetap
    // memakai warna tombol terpilih, tanggal nonaktif tetap abu-abu.
    modifiers: { sunday: { dayOfWeek: 0 } },
    modifiersClassNames: {
        sunday: 'not-data-[disabled=true]:text-destructive',
    },
    // Sel tepat size-8 (32px); grid 7 × 32px di tengah panel.
    className: 'mx-auto p-0 text-sm [--cell-size:--spacing(8)]',
    // Baris rapat: header hari 28px + 6 minggu × (32px + 2px).
    classNames: {
        root: 'mx-auto w-fit',
        month_caption: 'sr-only',
        weekdays: 'flex',
        // Kolom terakhir = Minggu (minggu dimulai Senin).
        weekday:
            'flex h-7 flex-1 items-center justify-center text-xs font-normal text-muted-foreground select-none last:text-destructive',
        week: 'mt-0.5 flex w-full',
        outside: 'invisible',
    },
} as const;

function HeaderButton({
    children,
    label,
    onClick,
}: {
    children: ReactNode;
    label: string;
    onClick: () => void;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="px-2 font-medium tabular-nums"
            aria-label={label}
            onClick={onClick}
        >
            {children}
        </Button>
    );
}

function NavButton({
    direction,
    label,
    disabled = false,
    onClick,
}: {
    direction: 'previous' | 'next';
    label: string;
    disabled?: boolean;
    onClick: () => void;
}) {
    const Icon = direction === 'previous' ? ChevronLeft : ChevronRight;

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
        >
            <Icon aria-hidden="true" />
        </Button>
    );
}

type ChoiceItem = {
    key: number;
    label: string;
    ariaLabel: string;
    /** Bulan/tahun yang sedang ditampilkan. */
    active: boolean;
    /** Bulan/tahun hari ini. */
    current: boolean;
    disabled: boolean;
    onSelect: () => void;
};

/** Grid pilihan bulan/tahun; tombol panah memindahkan fokus antar-sel. */
function ChoiceGrid({
    columns,
    label,
    items,
}: {
    columns: number;
    label: string;
    items: ChoiceItem[];
}) {
    const grid = useRef<HTMLDivElement>(null);
    // Berubah saat berpindah halaman tahun.
    const firstKey = items[0]?.key;

    // Fokus pindah ke pilihan aktif saat tampilan/halaman berganti, agar
    // navigasi keyboard tidak tertinggal di tombol yang sudah hilang.
    useEffect(() => {
        const target =
            grid.current?.querySelector<HTMLButtonElement>(
                'button[data-active=true]:not(:disabled)',
            ) ??
            grid.current?.querySelector<HTMLButtonElement>(
                'button:not(:disabled)',
            );

        target?.focus();
    }, [firstKey]);

    const moveFocus = (event: KeyboardEvent<HTMLDivElement>) => {
        const steps: Record<string, number> = {
            ArrowLeft: -1,
            ArrowRight: 1,
            ArrowUp: -columns,
            ArrowDown: columns,
        };
        const step = steps[event.key];

        if (step === undefined || !grid.current) {
            return;
        }

        const buttons = [...grid.current.querySelectorAll('button')];
        const index = buttons.indexOf(
            document.activeElement as HTMLButtonElement,
        );
        const next = buttons[index + step];

        if (index !== -1 && next && !next.disabled) {
            event.preventDefault();
            next.focus();
        }
    };

    return (
        <div
            ref={grid}
            role="group"
            aria-label={label}
            className="grid h-full gap-1"
            style={{
                gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
            }}
            onKeyDown={moveFocus}
        >
            {items.map((item) => (
                <Button
                    key={item.key}
                    type="button"
                    variant={item.active ? 'default' : 'ghost'}
                    className={cn(
                        'h-full min-w-0 px-0 tabular-nums',
                        !item.active && item.current && 'bg-muted',
                    )}
                    aria-label={item.ariaLabel}
                    aria-pressed={item.active}
                    aria-current={item.current ? 'date' : undefined}
                    data-active={item.active}
                    disabled={item.disabled}
                    onClick={item.onSelect}
                >
                    {item.label}
                </Button>
            ))}
        </div>
    );
}

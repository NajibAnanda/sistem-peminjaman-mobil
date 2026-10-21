import { usePage } from '@inertiajs/react';
import { CalendarRange } from 'lucide-react';
import type { DateRangePreset } from '@/components/date-picker/date-range-picker';
import {
    DateRangePicker,
    formatDateRange,
} from '@/components/date-picker/date-range-picker';
import { Button } from '@/components/ui/button';
import type { UseTableQueryReturn } from '@/hooks/use-table-query';
import {
    monthRange,
    shiftDate,
    shiftMonths,
    todayDate,
    yearRange,
} from '@/lib/datetime';

type Props = {
    table: UseTableQueryReturn;
};

const LAST_WEEK_DAYS = 6;
const LAST_QUARTER_MONTHS = 3;

/**
 * Filter periode tabel (Riwayat, Log Aktivitas) lewat pemilih rentang
 * tanggal atau pilihan cepat. Rentang disimpan di query string (?from=&to=)
 * sehingga bisa dibagikan dan ikut terbawa ke ekspor.
 */
export function DataTablePeriod({ table }: Props) {
    const { timezone } = usePage().props;
    const active = table.period.from !== '' || table.period.to !== '';
    const today = todayDate(timezone);

    // Semua pilihan cepat ≤ 366 hari, sehingga selalu bisa langsung diekspor.
    const presets: DateRangePreset[] = [
        { label: 'Hari ini', range: { from: today, to: today } },
        {
            label: '7 hari terakhir',
            range: { from: shiftDate(today, -LAST_WEEK_DAYS), to: today },
        },
        { label: 'Bulan ini', range: monthRange(today) },
        { label: 'Bulan lalu', range: monthRange(today, -1) },
        {
            label: '3 bulan terakhir',
            range: {
                from: shiftDate(shiftMonths(today, -LAST_QUARTER_MONTHS), 1),
                to: today,
            },
        },
        { label: 'Tahun ini', range: yearRange(today) },
    ];

    return (
        <DateRangePicker
            value={table.period}
            onChange={table.setPeriod}
            presets={presets}
            // Periode wajib (Riwayat): tidak bisa dikosongkan.
            clearLabel={table.periodRequired ? undefined : 'Semua periode'}
            align="end"
            trigger={
                <Button
                    variant={active ? 'secondary' : 'outline'}
                    className="max-w-full"
                    data-test="table-period-button"
                >
                    <CalendarRange aria-hidden="true" />
                    <span className="truncate">
                        {formatDateRange(table.period) ?? 'Periode'}
                    </span>
                </Button>
            }
        />
    );
}

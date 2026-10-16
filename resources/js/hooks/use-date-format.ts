import { usePage } from '@inertiajs/react';
import {
    formatDate,
    formatDateTime,
    formatTime,
    formatWeekday,
    toDateTimeLocal,
} from '@/lib/datetime';

type DateInput = string | number | Date;

export type UseDateFormatReturn = {
    formatDate: (value: DateInput) => string;
    formatTime: (value: DateInput) => string;
    formatDateTime: (value: DateInput) => string;
    toDateTimeLocal: (value: DateInput) => string;
    /** Label hari dari tanggal "Y-m-d", mis. "Sen, 13 Okt". */
    formatWeekday: (date: string) => string;
};

export function useDateFormat(): UseDateFormatReturn {
    const { timezone } = usePage().props;

    return {
        formatDate: (value) => formatDate(value, timezone),
        formatTime: (value) => formatTime(value, timezone),
        formatDateTime: (value) => formatDateTime(value, timezone),
        toDateTimeLocal: (value) => toDateTimeLocal(value, timezone),
        formatWeekday: (date) => formatWeekday(date, timezone),
    };
}

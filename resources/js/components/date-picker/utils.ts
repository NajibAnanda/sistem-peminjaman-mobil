import { formatDate, formatDateTime } from '@/lib/datetime';

/**
 * Nilai pemilih tanggal disimpan sebagai jam dinding di zona waktu aplikasi:
 * tanggal "2026-10-17" dan tanggal-waktu "2026-10-17T14:30". Objek Date
 * untuk kalender dibuat dari bagian tanggalnya saja (tengah malam lokal),
 * sehingga tidak bergantung pada zona waktu komputer pengguna.
 */

const LOCAL_DATE = /^(\d{4})-(\d{2})-(\d{2})/;
const LOCAL_TIME = /T(\d{2}):(\d{2})$/;

const pad = (value: number) => String(value).padStart(2, '0');

/** "2026-10-17" → Date tengah malam lokal (untuk kalender). */
export function parseDate(value: string): Date | undefined {
    const match = LOCAL_DATE.exec(value);

    return match
        ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
        : undefined;
}

/** Date kalender → "2026-10-17". */
export function toDateValue(date: Date): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** "2026-10-17T14:30" → { hour: 14, minute: 30 }. */
export function parseTime(value: string): { hour: number; minute: number } {
    const match = LOCAL_TIME.exec(value);

    return match
        ? { hour: Number(match[1]), minute: Number(match[2]) }
        : { hour: 0, minute: 0 };
}

export function toDateTimeValue(
    date: string,
    hour: number,
    minute: number,
): string {
    return `${date}T${pad(hour)}:${pad(minute)}`;
}

/** Jam dinding sebagai instan UTC, hanya untuk aritmetika & format. */
function wallClock(value: string): number {
    const date = parseDate(value) ?? new Date(0);
    const { hour, minute } = parseTime(value);

    return Date.UTC(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
        hour,
        minute,
    );
}

const MINUTE_MS = 60_000;

/** Selisih menit antara dua nilai "2026-10-17T14:30" (jam dinding). */
export function minutesBetween(start: string, end: string): number {
    return (wallClock(end) - wallClock(start)) / MINUTE_MS;
}

/** Menggeser nilai "2026-10-17T14:30" sejumlah menit (boleh negatif). */
export function addMinutes(value: string, minutes: number): string {
    return new Date(wallClock(value) + minutes * MINUTE_MS)
        .toISOString()
        .slice(0, 16);
}

/** Dibulatkan ke atas ke kelipatan menit, mis. 14.32 → 14.35. */
export function ceilToStep(value: string, step: number): string {
    const { minute } = parseTime(value);
    const rest = minute % step;

    return rest === 0 ? value : addMinutes(value, step - rest);
}

/** "17 Okt 2026" */
export function formatDateValue(value: string): string {
    return formatDate(wallClock(`${value.slice(0, 10)}T00:00`), 'UTC');
}

/** "17 Okt 2026, 14.30" */
export function formatDateTimeValue(value: string): string {
    return formatDateTime(wallClock(value), 'UTC');
}

const MONTH_NAMES = (month: 'long' | 'short') =>
    Array.from({ length: 12 }, (_, index) =>
        new Intl.DateTimeFormat('id-ID', { month, timeZone: 'UTC' }).format(
            Date.UTC(2000, index, 15),
        ),
    );

/** Nama bulan Bahasa Indonesia, sama dengan format tanggal aplikasi. */
export const MONTHS_LONG = MONTH_NAMES('long');
export const MONTHS_SHORT = MONTH_NAMES('short');

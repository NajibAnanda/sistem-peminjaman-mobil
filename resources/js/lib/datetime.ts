/**
 * Helper format tanggal terpusat (prd.md NFR-11), contoh: "16 Okt 2026, 14.30".
 * Zona waktu selalu diberikan dari server (`timezone` di props bersama) agar
 * tampilan tidak bergantung pada pengaturan jam komputer pengguna.
 */

const LOCALE = 'id-ID';

type DateInput = string | number | Date;

function toDate(value: DateInput): Date {
    return value instanceof Date ? value : new Date(value);
}

export function formatDate(value: DateInput, timeZone: string): string {
    return new Intl.DateTimeFormat(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone,
    }).format(toDate(value));
}

export function formatTime(value: DateInput, timeZone: string): string {
    return new Intl.DateTimeFormat(LOCALE, {
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone,
    }).format(toDate(value));
}

export function formatDateTime(value: DateInput, timeZone: string): string {
    // Digabung manual karena beberapa versi ICU menyisipkan kata "pukul".
    return `${formatDate(value, timeZone)}, ${formatTime(value, timeZone)}`;
}

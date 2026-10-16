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

/**
 * Nilai untuk `<input type="datetime-local">` ("2026-10-16T14:30") dalam zona
 * waktu aplikasi; server membacanya kembali dengan zona waktu yang sama.
 */
export function toDateTimeLocal(value: DateInput, timeZone: string): string {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-CA', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
            timeZone,
        })
            .formatToParts(toDate(value))
            .map((part) => [part.type, part.value]),
    );

    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
}

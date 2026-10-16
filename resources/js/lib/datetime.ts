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

const MINUTE_MS = 60_000;
const MINUTES_PER_HOUR = 60;
const HOURS_PER_DAY = 24;

/**
 * Lama waktu antara dua titik, mis. lama keterlambatan: "2 hari 3 jam",
 * "3 jam 15 menit", atau "15 menit".
 */
export function formatDuration(from: DateInput, to: DateInput): string {
    const totalMinutes = Math.max(
        1,
        Math.floor((toDate(to).getTime() - toDate(from).getTime()) / MINUTE_MS),
    );
    const totalHours = Math.floor(totalMinutes / MINUTES_PER_HOUR);
    const days = Math.floor(totalHours / HOURS_PER_DAY);
    const hours = totalHours % HOURS_PER_DAY;
    const minutes = totalMinutes % MINUTES_PER_HOUR;

    if (days > 0) {
        return hours > 0 ? `${days} hari ${hours} jam` : `${days} hari`;
    }

    if (totalHours > 0) {
        return minutes > 0
            ? `${totalHours} jam ${minutes} menit`
            : `${totalHours} jam`;
    }

    return `${minutes} menit`;
}

/**
 * Label hari untuk kolom timeline, mis. "Sen, 13 Okt". Menerima tanggal
 * "Y-m-d" dari server; dibaca pada tengah hari UTC agar tidak bergeser hari
 * karena zona waktu.
 */
export function formatWeekday(date: string, timeZone: string): string {
    return new Intl.DateTimeFormat(LOCALE, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        timeZone,
    }).format(new Date(`${date}T12:00:00Z`));
}

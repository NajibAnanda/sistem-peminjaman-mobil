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
 * Jam dinding "2026-10-16T14:30" dalam zona waktu aplikasi, format nilai
 * pemilih tanggal-waktu. Dikirim ke server lewat toZonedIso().
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

/** Selisih zona waktu terhadap UTC dalam menit pada saat tertentu (WIB = 420). */
function offsetMinutes(instant: number, timeZone: string): number {
    const name =
        new Intl.DateTimeFormat('en-US', {
            timeZone,
            timeZoneName: 'longOffset',
        })
            .formatToParts(new Date(instant))
            .find((part) => part.type === 'timeZoneName')?.value ?? 'GMT';
    const match = /GMT([+-])(\d{2}):(\d{2})/.exec(name);

    if (!match) {
        return 0;
    }

    const minutes = Number(match[2]) * MINUTES_PER_HOUR + Number(match[3]);

    return match[1] === '-' ? -minutes : minutes;
}

/**
 * Nilai lokal "2026-10-16T14:30" (jam dinding di zona waktu aplikasi) menjadi
 * ISO 8601 dengan offset, mis. "2026-10-16T14:30:00+07:00", untuk dikirim ke
 * server. String kosong tetap kosong agar validasi server yang menolaknya.
 */
export function toZonedIso(local: string, timeZone: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/.exec(local);

    if (!match) {
        return local;
    }

    const [, year, month, day, hour, minute] = match.map(Number);
    const wallClock = Date.UTC(year, month - 1, day, hour, minute);
    // Offset dihitung pada instan sebenarnya (penting untuk zona ber-DST).
    const offset = offsetMinutes(
        wallClock - offsetMinutes(wallClock, timeZone) * MINUTE_MS,
        timeZone,
    );
    const sign = offset < 0 ? '-' : '+';
    const absolute = Math.abs(offset);
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${local}:00${sign}${pad(Math.floor(absolute / MINUTES_PER_HOUR))}:${pad(absolute % MINUTES_PER_HOUR)}`;
}

/**
 * Lama waktu dalam menit: "2 hari 3 jam", "3 jam 15 menit", atau "15 menit".
 * Sama dengan App\Support\DateFormat::duration() di server.
 */
export function formatMinutes(minutes: number): string {
    const totalMinutes = Math.max(1, Math.floor(minutes));
    const totalHours = Math.floor(totalMinutes / MINUTES_PER_HOUR);
    const days = Math.floor(totalHours / HOURS_PER_DAY);
    const hours = totalHours % HOURS_PER_DAY;
    const rest = totalMinutes % MINUTES_PER_HOUR;

    if (days > 0) {
        return hours > 0 ? `${days} hari ${hours} jam` : `${days} hari`;
    }

    if (totalHours > 0) {
        return rest > 0
            ? `${totalHours} jam ${rest} menit`
            : `${totalHours} jam`;
    }

    return `${rest} menit`;
}

/** Lama waktu antara dua titik, mis. lama keterlambatan. */
export function formatDuration(from: DateInput, to: DateInput): string {
    return formatMinutes(
        (toDate(to).getTime() - toDate(from).getTime()) / MINUTE_MS,
    );
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

const DAY_MS = 86_400_000;

function parseDate(date: string): Date {
    return new Date(`${date}T00:00:00Z`);
}

function toDateString(date: Date): string {
    return date.toISOString().slice(0, 10);
}

/** Tanggal hari ini (Y-m-d) di zona waktu aplikasi. */
export function todayDate(timeZone: string): string {
    return toDateTimeLocal(new Date(), timeZone).slice(0, 10);
}

/** Menggeser tanggal "Y-m-d" sejumlah hari (boleh negatif). */
export function shiftDate(date: string, days: number): string {
    return toDateString(new Date(parseDate(date).getTime() + days * DAY_MS));
}

/**
 * Menggeser tanggal "Y-m-d" sejumlah bulan; tanggal yang tidak ada di bulan
 * tujuan (mis. 31 Februari) memakai hari terakhir bulan itu.
 */
export function shiftMonths(date: string, months: number): string {
    const base = parseDate(date);
    const lastDay = new Date(
        Date.UTC(base.getUTCFullYear(), base.getUTCMonth() + months + 1, 0),
    ).getUTCDate();

    return toDateString(
        new Date(
            Date.UTC(
                base.getUTCFullYear(),
                base.getUTCMonth() + months,
                Math.min(base.getUTCDate(), lastDay),
            ),
        ),
    );
}

/** 1 Januari – 31 Desember tahun dari tanggal "Y-m-d". */
export function yearRange(date: string): { from: string; to: string } {
    const year = date.slice(0, 4);

    return { from: `${year}-01-01`, to: `${year}-12-31` };
}

/** Tanggal pertama & terakhir bulan dari tanggal "Y-m-d"; `offset` -1 = bulan lalu. */
export function monthRange(
    date: string,
    offset = 0,
): { from: string; to: string } {
    const base = parseDate(date);
    const first = new Date(
        Date.UTC(base.getUTCFullYear(), base.getUTCMonth() + offset, 1),
    );
    const last = new Date(
        Date.UTC(base.getUTCFullYear(), base.getUTCMonth() + offset + 1, 0),
    );

    return { from: toDateString(first), to: toDateString(last) };
}

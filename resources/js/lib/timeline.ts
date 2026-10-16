import type { ScheduleBooking } from '@/types';

export type TimelineBlock = {
    booking: ScheduleBooking;
    /** Posisi dan lebar dalam persen terhadap lebar satu minggu. */
    left: number;
    width: number;
    lane: number;
    /** Peminjaman berlanjut dari minggu sebelumnya / ke minggu berikutnya. */
    continuesBefore: boolean;
    continuesAfter: boolean;
};

export type TimelineRow = {
    blocks: TimelineBlock[];
    lanes: number;
};

const PERCENT = 100;

/**
 * Menempatkan blok peminjaman per mobil pada timeline mingguan. Blok yang
 * tumpang tindih (mis. riwayat dengan waktu aktual) ditaruh di lajur berbeda
 * agar tidak saling menutupi.
 */
export function layoutTimeline(
    bookings: ScheduleBooking[],
    weekStart: string,
    weekEnd: string,
): Map<number, TimelineRow> {
    const startMs = new Date(weekStart).getTime();
    const endMs = new Date(weekEnd).getTime();
    const span = endMs - startMs;
    const rows = new Map<number, TimelineRow>();
    // Akhir blok terakhir di setiap lajur, per mobil.
    const laneEnds = new Map<number, number[]>();

    const sorted = [...bookings].sort(
        (a, b) =>
            new Date(a.start_at).getTime() - new Date(b.start_at).getTime(),
    );

    for (const booking of sorted) {
        const bookingStart = new Date(booking.start_at).getTime();
        const bookingEnd = new Date(booking.end_at).getTime();
        const from = Math.max(bookingStart, startMs);
        const to = Math.min(bookingEnd, endMs);

        const ends = laneEnds.get(booking.car_id) ?? [];
        let lane = ends.findIndex((end) => end <= from);

        if (lane === -1) {
            lane = ends.length;
        }

        ends[lane] = to;
        laneEnds.set(booking.car_id, ends);

        const row = rows.get(booking.car_id) ?? { blocks: [], lanes: 0 };
        row.blocks.push({
            booking,
            left: ((from - startMs) / span) * PERCENT,
            width: (Math.max(to - from, 0) / span) * PERCENT,
            lane,
            continuesBefore: bookingStart < startMs,
            continuesAfter: bookingEnd > endMs,
        });
        row.lanes = Math.max(row.lanes, lane + 1);
        rows.set(booking.car_id, row);
    }

    return rows;
}

/** Posisi garis "sekarang" dalam persen, atau null bila di luar minggu. */
export function nowPosition(
    now: string,
    weekStart: string,
    weekEnd: string,
): number | null {
    const startMs = new Date(weekStart).getTime();
    const endMs = new Date(weekEnd).getTime();
    const nowMs = new Date(now).getTime();

    if (nowMs < startMs || nowMs >= endMs) {
        return null;
    }

    return ((nowMs - startMs) / (endMs - startMs)) * PERCENT;
}

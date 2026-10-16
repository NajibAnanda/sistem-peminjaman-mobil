import type { Booking, BookingDisplayStatus } from '@/types/booking';
import type { CarDisplayStatus } from '@/types/car';

export type FleetSummaryItem = {
    status: CarDisplayStatus;
    label: string;
    count: number;
};

/**
 * Sinkron dengan App\Http\Controllers\DashboardController.
 */
export type DashboardProps = {
    /** Waktu server, acuan lama keterlambatan. */
    now: string;
    fleet: FleetSummaryItem[];
    pickupsToday: Booking[];
    returnsToday: Booking[];
    overdue: Booking[];
    notPickedUp: Booking[];
};

export type ScheduleCar = {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    display_status: CarDisplayStatus;
};

/** Blok peminjaman di timeline; waktu sudah memakai waktu aktual bila ada. */
export type ScheduleBooking = {
    id: number;
    code: string;
    car_id: number;
    employee_name: string;
    purpose: string;
    display_status: BookingDisplayStatus;
    display_status_label: string;
    start_at: string;
    end_at: string;
};

/**
 * Sinkron dengan App\Http\Controllers\ScheduleController.
 */
export type ScheduleProps = {
    now: string;
    week: {
        start: string;
        end: string;
        /** Tanggal (Y-m-d) untuk parameter `week`. */
        previous: string;
        next: string;
        is_current: boolean;
        days: string[];
    };
    cars: ScheduleCar[];
    bookings: ScheduleBooking[];
};

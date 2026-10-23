import type { Option, Paginated } from '@/types/pagination';
import type { TableQueryState } from '@/types/table';

export type BookingStatus = 'scheduled' | 'active' | 'completed' | 'cancelled';

/** Status tersimpan + indikator turunan (Terlambat, Belum Diambil). */
export type BookingDisplayStatus = BookingStatus | 'overdue' | 'not_picked_up';

export type PhotoSource = 'camera' | 'upload';

export type BookingActions = {
    edit: boolean;
    cancel: boolean;
    pickup: boolean;
    extend: boolean;
    return: boolean;
};

/**
 * Sinkron dengan App\Http\Resources\BookingResource.
 */
export type Booking = {
    id: number;
    code: string;
    status: BookingStatus;
    display_status: BookingDisplayStatus;
    display_status_label: string;
    purpose: string;
    start_at: string;
    planned_return_at: string;
    picked_up_at: string | null;
    /** Kode peminjaman Terlambat yang masih memakai mobil ini (FR-23). */
    awaiting_return_code: string | null;
    returned_at: string | null;
    employee: {
        id: number;
        name: string;
        division: string;
        phone: string;
    };
    car: {
        id: number;
        plate_number: string;
        brand: string;
        model: string;
    };
    actions: BookingActions;
};

export type BookingPhoto = {
    url: string;
    taken_at: string;
    source_label: string;
    uploaded_by_name: string;
};

export type BookingExtension = {
    id: number;
    previous_planned_return_at: string;
    new_planned_return_at: string;
    reason: string;
    extended_by_name: string;
    created_at: string;
};

/**
 * Sinkron dengan App\Http\Resources\BookingDetailResource.
 */
export type BookingDetail = Booking & {
    return_notes: string | null;
    cancellation_reason: string | null;
    cancelled_at: string | null;
    created_at: string;
    created_by_name: string;
    picked_up_by_name: string | null;
    returned_by_name: string | null;
    cancelled_by_name: string | null;
    photos: {
        pickup: BookingPhoto | null;
        return: BookingPhoto | null;
    };
    extensions: BookingExtension[];
};

/** Pilihan karyawan di form jadwal (BookingController::form). */
export type EmployeeOption = {
    id: number;
    name: string;
    division: string;
    phone: string;
};

/**
 * Pilihan mobil beserta ketersediaannya (App\Support\CarAvailability).
 * `available` null berarti rentang waktu belum dipilih.
 */
export type CarOption = {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    available: boolean | null;
    reason: string | null;
};

/** Sinkron dengan App\Support\BookingAlerts::counts(). */
export type BookingAlerts = {
    overdue: number;
    not_picked_up: number;
};

export type BookingIndexProps = {
    bookings: Paginated<Booking>;
    query: TableQueryState;
    statuses: Option<BookingDisplayStatus>[];
    maxUploadKb: number;
};

export type BookingFormProps = {
    booking: Booking | null;
    employees: EmployeeOption[];
    cars: CarOption[];
    startToleranceMinutes: number;
};

export type BookingShowProps = {
    booking: BookingDetail;
    maxUploadKb: number;
};

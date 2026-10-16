import type { Option, Paginated } from '@/types/pagination';
import type { TableQueryState } from '@/types/table';

export type CarDisplayStatus =
    | 'available'
    | 'borrowed'
    | 'maintenance'
    | 'inactive';

export type CarOperationalStatus = 'operational' | 'maintenance';

export type CarPhotoSlot = {
    position: number;
    url: string | null;
};

/**
 * Sinkron dengan App\Http\Resources\CarResource.
 */
export type Car = {
    id: number;
    plate_number: string;
    brand: string;
    model: string;
    operational_status: CarOperationalStatus;
    is_active: boolean;
    display_status: CarDisplayStatus;
    display_status_label: string;
    photos_complete: boolean;
    photos: CarPhotoSlot[];
};

/**
 * Sinkron dengan App\Http\Resources\BookingSummaryResource.
 */
export type BookingSummary = {
    id: number;
    code: string;
    employee_name: string;
    purpose: string;
    start_at: string;
    planned_return_at: string;
};

export type CarIndexProps = {
    cars: Paginated<Car>;
    query: TableQueryState;
    statuses: Option<CarDisplayStatus>[];
};

export type CarShowProps = {
    car: Car;
    activeBooking: BookingSummary | null;
    scheduledBookings: BookingSummary[];
    maxUploadKb: number;
};

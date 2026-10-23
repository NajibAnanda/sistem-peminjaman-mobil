import type { Booking } from '@/types/booking';
import type { Option, Paginated } from '@/types/pagination';
import type { TableQueryState } from '@/types/table';

/**
 * Sinkron dengan App\Http\Resources\HistoryResource.
 */
export type HistoryBooking = Booking & {
    extensions_count: number;
    /** Lama keterlambatan kembali; null bila tidak terlambat. */
    lateness_minutes: number | null;
};

export type HistoryIndexProps = {
    bookings: Paginated<HistoryBooking>;
    query: TableQueryState;
    filterOptions: {
        status: Option[];
        lateness: Option[];
        division: Option[];
        car: Option[];
        employee: Option[];
    };
    exportFormats: Option<'xlsx' | 'csv' | 'pdf'>[];
};

export type ActivityChange = {
    field: string;
    label: string;
    old: string | null;
    new: string | null;
};

/**
 * Sinkron dengan App\Support\ActivityPresenter::present().
 */
export type ActivityEntry = {
    id: number;
    created_at: string;
    causer_name: string;
    type: string | null;
    type_label: string;
    event_label: string | null;
    description: string;
    subject_label: string | null;
    changes: ActivityChange[];
    details: { label: string; value: string }[];
};

export type ActivityLogIndexProps = {
    activities: Paginated<ActivityEntry>;
    query: TableQueryState;
    filterOptions: {
        type: Option[];
        staff: Option[];
    };
};

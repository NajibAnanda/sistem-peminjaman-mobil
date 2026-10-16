import type { StaffRole } from '@/types/auth';
import type { Option, Paginated } from '@/types/pagination';
import type { TableQueryState } from '@/types/table';

/**
 * Sinkron dengan App\Http\Resources\StaffResource.
 */
export type StaffMember = {
    id: number;
    name: string;
    email: string;
    role: StaffRole;
    role_label: string;
    is_active: boolean;
    two_factor_enabled: boolean;
    must_change_password: boolean;
    last_login_at: string | null;
    is_self: boolean;
};

export type StaffIndexProps = {
    staff: Paginated<StaffMember>;
    query: TableQueryState;
    roles: Option<StaffRole>[];
    statuses: Option[];
};

/**
 * Flash sekali-tampil setelah akun dibuat atau password direset.
 */
export type TemporaryPasswordFlash = {
    name: string;
    email: string;
    password: string;
};

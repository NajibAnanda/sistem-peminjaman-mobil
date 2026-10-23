import type { Option, Paginated } from '@/types/pagination';
import type { TableQueryState } from '@/types/table';

/**
 * Sinkron dengan App\Http\Resources\EmployeeResource.
 */
export type Employee = {
    id: number;
    name: string;
    division: string;
    phone: string;
    is_active: boolean;
};

export type DivisionOption = Option & {
    can_delete?: boolean;
};

export type EmployeeIndexProps = {
    employees: Paginated<Employee>;
    query: TableQueryState;
    divisions: DivisionOption[];
    statuses: Option[];
};

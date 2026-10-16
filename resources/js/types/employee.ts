import type { Option, Paginated } from '@/types/pagination';
import type { TableQueryState } from '@/types/table';

/**
 * Sinkron dengan App\Http\Resources\EmployeeResource.
 */
export type Employee = {
    id: number;
    employee_number: string;
    name: string;
    division: string;
    phone: string;
    is_active: boolean;
};

export type EmployeeIndexProps = {
    employees: Paginated<Employee>;
    query: TableQueryState;
    divisions: Option[];
    statuses: Option[];
};

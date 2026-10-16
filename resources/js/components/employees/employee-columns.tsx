import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import type { Employee } from '@/types';

export function useEmployeeColumns(): DataTableColumn<Employee>[] {
    return [
        {
            id: 'employee_number',
            header: 'NIP',
            cell: (employee) => (
                <span className="tabular-nums">{employee.employee_number}</span>
            ),
        },
        {
            id: 'name',
            header: 'Nama',
            cell: (employee) => (
                <span className="font-medium">{employee.name}</span>
            ),
        },
        {
            id: 'division',
            header: 'Divisi',
            cell: (employee) => employee.division,
        },
        {
            id: 'phone',
            header: 'No HP',
            cell: (employee) => (
                <span className="tabular-nums">{employee.phone}</span>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (employee) => (
                <StatusBadge
                    status={employee.is_active ? 'enabled' : 'inactive'}
                />
            ),
        },
    ];
}

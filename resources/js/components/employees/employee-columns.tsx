import { TruncatedText } from '@/components/data-table/truncated-text';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import type { Employee } from '@/types';

export function useEmployeeColumns(): DataTableColumn<Employee>[] {
    return [
        {
            id: 'name',
            header: 'Nama',
            cell: (employee) => (
                <TruncatedText
                    text={employee.name}
                    className="max-w-64 font-medium"
                />
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
            skeleton: 'badge',
            cell: (employee) => (
                <StatusBadge
                    status={employee.is_active ? 'enabled' : 'inactive'}
                />
            ),
        },
    ];
}

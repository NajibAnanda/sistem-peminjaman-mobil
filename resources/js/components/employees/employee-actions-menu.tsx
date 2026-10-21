import { Pencil, UserCheck, UserX } from 'lucide-react';
import { RowActions } from '@/components/data-table/row-actions';
import type { Employee } from '@/types';

export type EmployeeAction = 'edit' | 'toggle-active';

type Props = {
    employee: Employee;
    onAction: (action: EmployeeAction, employee: Employee) => void;
};

export function EmployeeActionsMenu({ employee, onAction }: Props) {
    return (
        <RowActions
            label={`Aksi untuk ${employee.name}`}
            actions={[
                {
                    label: 'Edit',
                    icon: Pencil,
                    onSelect: () => onAction('edit', employee),
                },
                {
                    label: 'Aktifkan',
                    icon: UserCheck,
                    onSelect: () => onAction('toggle-active', employee),
                    hidden: employee.is_active,
                },
                {
                    label: 'Nonaktifkan',
                    icon: UserX,
                    onSelect: () => onAction('toggle-active', employee),
                    destructive: true,
                    hidden: !employee.is_active,
                },
            ]}
        />
    );
}

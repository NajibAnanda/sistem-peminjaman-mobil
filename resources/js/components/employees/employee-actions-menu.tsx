import { MoreHorizontal, Pencil, UserCheck, UserX } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Employee } from '@/types';

export type EmployeeAction = 'edit' | 'toggle-active';

type Props = {
    employee: Employee;
    onAction: (action: EmployeeAction, employee: Employee) => void;
};

export function EmployeeActionsMenu({ employee, onAction }: Props) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`Aksi untuk ${employee.name}`}
                >
                    <MoreHorizontal aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onSelect={() => onAction('edit', employee)}>
                    <Pencil aria-hidden="true" />
                    Edit
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    variant={employee.is_active ? 'destructive' : 'default'}
                    onSelect={() => onAction('toggle-active', employee)}
                >
                    {employee.is_active ? (
                        <UserX aria-hidden="true" />
                    ) : (
                        <UserCheck aria-hidden="true" />
                    )}
                    {employee.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

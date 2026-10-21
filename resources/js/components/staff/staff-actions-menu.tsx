import { KeyRound, Pencil, UserCheck, UserX } from 'lucide-react';
import { RowActions } from '@/components/data-table/row-actions';
import type { StaffMember } from '@/types';

export type StaffAction = 'edit' | 'reset' | 'deactivate' | 'activate';

type Props = {
    staff: StaffMember;
    onAction: (action: StaffAction, staff: StaffMember) => void;
};

/**
 * Aksi untuk diri sendiri (reset password, nonaktifkan) disembunyikan demi
 * kenyamanan; aturan sebenarnya tetap dijaga di server.
 */
export function StaffActionsMenu({ staff, onAction }: Props) {
    return (
        <RowActions
            label={`Aksi untuk ${staff.name}`}
            actions={[
                {
                    label: 'Edit',
                    icon: Pencil,
                    onSelect: () => onAction('edit', staff),
                },
                {
                    label: 'Aktifkan',
                    icon: UserCheck,
                    onSelect: () => onAction('activate', staff),
                    hidden: staff.is_self || staff.is_active,
                },
                {
                    label: 'Reset password',
                    icon: KeyRound,
                    onSelect: () => onAction('reset', staff),
                    destructive: true,
                    hidden: staff.is_self,
                },
                {
                    label: 'Nonaktifkan',
                    icon: UserX,
                    onSelect: () => onAction('deactivate', staff),
                    destructive: true,
                    hidden: staff.is_self || !staff.is_active,
                },
            ]}
        />
    );
}

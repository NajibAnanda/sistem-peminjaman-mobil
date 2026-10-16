import {
    KeyRound,
    MoreHorizontal,
    Pencil,
    UserCheck,
    UserX,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`Aksi untuk ${staff.name}`}
                >
                    <MoreHorizontal aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onSelect={() => onAction('edit', staff)}>
                    <Pencil aria-hidden="true" />
                    Edit
                </DropdownMenuItem>

                {!staff.is_self && (
                    <DropdownMenuItem onSelect={() => onAction('reset', staff)}>
                        <KeyRound aria-hidden="true" />
                        Reset password
                    </DropdownMenuItem>
                )}

                {!staff.is_self && (
                    <>
                        <DropdownMenuSeparator />
                        {staff.is_active ? (
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => onAction('deactivate', staff)}
                            >
                                <UserX aria-hidden="true" />
                                Nonaktifkan
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem
                                onSelect={() => onAction('activate', staff)}
                            >
                                <UserCheck aria-hidden="true" />
                                Aktifkan
                            </DropdownMenuItem>
                        )}
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

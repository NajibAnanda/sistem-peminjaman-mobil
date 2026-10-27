import { KeyRound, ShieldCheck, ShieldOff } from 'lucide-react';
import { useMemo } from 'react';
import { CellNote } from '@/components/data-table/cell-note';
import { TruncatedText } from '@/components/data-table/truncated-text';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/hooks/use-date-format';
import type { StaffMember } from '@/types';

export function useStaffColumns(): DataTableColumn<StaffMember>[] {
    const { formatDateTime } = useDateFormat();

    return useMemo(
        () => [
            {
                id: 'name',
                header: 'Nama',
                cell: (member) => (
                    <span className="inline-flex max-w-64 items-center gap-2">
                        <TruncatedText
                            text={member.name}
                            className="min-w-0 font-medium"
                        />
                        {member.is_self && (
                            <Badge variant="secondary">Anda</Badge>
                        )}
                    </span>
                ),
            },
            {
                id: 'email',
                header: 'Email',
                cell: (member) => (
                    <TruncatedText text={member.email} className="max-w-64" />
                ),
            },
            {
                id: 'role',
                header: 'Peran',
                skeleton: 'badge',
                cell: (member) => (
                    <Badge variant="secondary">{member.role_label}</Badge>
                ),
            },
            {
                id: 'status',
                header: 'Status',
                skeleton: 'badge',
                cell: (member) => (
                    <span className="inline-flex items-center gap-1.5">
                        <StatusBadge
                            status={member.is_active ? 'enabled' : 'inactive'}
                        />
                        {member.must_change_password && (
                            <CellNote
                                icon={KeyRound}
                                tone="muted"
                                text="Belum ganti password sementara"
                            />
                        )}
                    </span>
                ),
            },
            {
                id: 'two_factor',
                header: '2FA',
                skeleton: 'badge',
                cell: (member) =>
                    member.two_factor_enabled ? (
                        <span className="inline-flex items-center gap-1 text-success">
                            <ShieldCheck
                                className="size-4"
                                aria-hidden="true"
                            />
                            Aktif
                        </span>
                    ) : (
                        <span className="inline-flex items-center gap-1 text-muted-foreground">
                            <ShieldOff className="size-4" aria-hidden="true" />
                            Belum aktif
                        </span>
                    ),
            },
            {
                id: 'last_login_at',
                header: 'Login terakhir',
                skeleton: 'date',
                cell: (member) =>
                    member.last_login_at ? (
                        formatDateTime(member.last_login_at)
                    ) : (
                        <span className="text-muted-foreground">
                            Belum pernah
                        </span>
                    ),
            },
        ],
        [formatDateTime],
    );
}

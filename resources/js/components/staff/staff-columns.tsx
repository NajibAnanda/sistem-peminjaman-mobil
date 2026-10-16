import { ShieldCheck, ShieldOff } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table/types';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/hooks/use-date-format';
import type { StaffMember } from '@/types';

export function useStaffColumns(): DataTableColumn<StaffMember>[] {
    const { formatDateTime } = useDateFormat();

    return [
        {
            id: 'name',
            header: 'Nama',
            cell: (member) => (
                <>
                    <div className="flex items-center gap-2 font-medium">
                        {member.name}
                        {member.is_self && (
                            <Badge variant="secondary">Anda</Badge>
                        )}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {member.email}
                    </div>
                </>
            ),
        },
        {
            id: 'role',
            header: 'Peran',
            cell: (member) => member.role_label,
        },
        {
            id: 'status',
            header: 'Status',
            cell: (member) => (
                <div className="flex flex-col items-start gap-1">
                    <StatusBadge
                        status={member.is_active ? 'enabled' : 'inactive'}
                    />
                    {member.must_change_password && (
                        <span className="text-xs text-muted-foreground">
                            Belum ganti password sementara
                        </span>
                    )}
                </div>
            ),
        },
        {
            id: 'two_factor',
            header: '2FA',
            cell: (member) =>
                member.two_factor_enabled ? (
                    <span className="inline-flex items-center gap-1 text-success">
                        <ShieldCheck className="size-4" aria-hidden="true" />
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
            className: 'whitespace-nowrap',
            cell: (member) =>
                member.last_login_at ? (
                    formatDateTime(member.last_login_at)
                ) : (
                    <span className="text-muted-foreground">Belum pernah</span>
                ),
        },
    ];
}

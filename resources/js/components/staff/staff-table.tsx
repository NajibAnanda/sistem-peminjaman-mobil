import { ShieldCheck, ShieldOff } from 'lucide-react';
import { StatusBadge } from '@/components/status-badge';
import type { StaffAction } from '@/components/staff/staff-actions-menu';
import { StaffActionsMenu } from '@/components/staff/staff-actions-menu';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDateFormat } from '@/hooks/use-date-format';
import type { StaffMember } from '@/types';

type Props = {
    staff: StaffMember[];
    onAction: (action: StaffAction, staff: StaffMember) => void;
};

export function StaffTable({ staff, onAction }: Props) {
    const { formatDateTime } = useDateFormat();

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Nama</TableHead>
                    <TableHead>Peran</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>2FA</TableHead>
                    <TableHead>Login terakhir</TableHead>
                    <TableHead className="w-12">
                        <span className="sr-only">Aksi</span>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {staff.map((member) => (
                    <TableRow key={member.id}>
                        <TableCell>
                            <div className="flex items-center gap-2 font-medium">
                                {member.name}
                                {member.is_self && (
                                    <Badge variant="secondary">Anda</Badge>
                                )}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                {member.email}
                            </div>
                        </TableCell>
                        <TableCell>{member.role_label}</TableCell>
                        <TableCell>
                            <div className="flex flex-col items-start gap-1">
                                <StatusBadge
                                    status={
                                        member.is_active
                                            ? 'enabled'
                                            : 'inactive'
                                    }
                                />
                                {member.must_change_password && (
                                    <span className="text-xs text-muted-foreground">
                                        Belum ganti password sementara
                                    </span>
                                )}
                            </div>
                        </TableCell>
                        <TableCell>
                            {member.two_factor_enabled ? (
                                <span className="inline-flex items-center gap-1 text-sm text-success">
                                    <ShieldCheck
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Aktif
                                </span>
                            ) : (
                                <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                                    <ShieldOff
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Belum aktif
                                </span>
                            )}
                        </TableCell>
                        <TableCell className="text-sm whitespace-nowrap">
                            {member.last_login_at ? (
                                formatDateTime(member.last_login_at)
                            ) : (
                                <span className="text-muted-foreground">
                                    Belum pernah
                                </span>
                            )}
                        </TableCell>
                        <TableCell className="text-right">
                            <StaffActionsMenu
                                staff={member}
                                onAction={onAction}
                            />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

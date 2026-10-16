import type { LucideIcon } from 'lucide-react';
import {
    AlarmClockOff,
    Ban,
    CalendarClock,
    CarFront,
    CircleCheck,
    CircleOff,
    Clock,
    KeyRound,
    Wrench,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/**
 * Semua status yang ditampilkan aplikasi: status tersimpan peminjaman,
 * indikator turunan, dan status tampilan mobil (prd.md section 5).
 */
export type StatusKey =
    | 'scheduled'
    | 'active'
    | 'completed'
    | 'cancelled'
    | 'overdue'
    | 'not_picked_up'
    | 'available'
    | 'borrowed'
    | 'maintenance'
    | 'inactive';

type Tone = 'success' | 'primary' | 'warning' | 'destructive' | 'muted';

// Pemetaan nada → token semantik; satu-satunya tempat warna status ditentukan.
const toneClasses: Record<Tone, string> = {
    success: 'bg-success/10 text-success dark:bg-success/20',
    primary:
        'bg-primary/10 text-primary dark:bg-primary/25 dark:text-primary-foreground',
    warning: 'bg-warning/10 text-warning dark:bg-warning/20',
    destructive: 'bg-destructive/10 text-destructive dark:bg-destructive/20',
    muted: 'bg-muted text-muted-foreground',
};

const statuses: Record<
    StatusKey,
    { label: string; tone: Tone; icon: LucideIcon }
> = {
    scheduled: { label: 'Dijadwalkan', tone: 'primary', icon: CalendarClock },
    active: { label: 'Aktif', tone: 'primary', icon: KeyRound },
    completed: { label: 'Selesai', tone: 'success', icon: CircleCheck },
    cancelled: { label: 'Dibatalkan', tone: 'muted', icon: Ban },
    overdue: { label: 'Terlambat', tone: 'destructive', icon: AlarmClockOff },
    not_picked_up: { label: 'Belum Diambil', tone: 'warning', icon: Clock },
    available: { label: 'Tersedia', tone: 'success', icon: CircleCheck },
    borrowed: { label: 'Dipinjam', tone: 'primary', icon: CarFront },
    maintenance: { label: 'Maintenance', tone: 'warning', icon: Wrench },
    inactive: { label: 'Nonaktif', tone: 'muted', icon: CircleOff },
};

type Props = {
    status: StatusKey;
    className?: string;
};

export function StatusBadge({ status, className }: Props) {
    const { label, tone, icon: Icon } = statuses[status];

    return (
        <Badge
            variant="outline"
            className={cn('border-transparent', toneClasses[tone], className)}
        >
            <Icon aria-hidden="true" />
            {label}
        </Badge>
    );
}

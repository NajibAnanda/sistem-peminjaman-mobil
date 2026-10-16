import { Link } from '@inertiajs/react';
import { Eye, MoreHorizontal, Pencil, Power, PowerOff } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { show } from '@/routes/cars';
import type { Car } from '@/types';

export type CarAction = 'edit' | 'deactivate' | 'activate';

type Props = {
    car: Car;
    onAction: (action: CarAction, car: Car) => void;
};

/**
 * Ubah status operasional sengaja hanya di halaman detail, karena di sana
 * daftar jadwal mendatang ditampilkan sebagai peringatan (FR-07).
 */
export function CarActionsMenu({ car, onAction }: Props) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`Aksi untuk ${car.plate_number}`}
                >
                    <MoreHorizontal aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                    <Link href={show(car.id)}>
                        <Eye aria-hidden="true" />
                        Lihat detail
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem onSelect={() => onAction('edit', car)}>
                    <Pencil aria-hidden="true" />
                    Edit
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                {car.is_active ? (
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => onAction('deactivate', car)}
                    >
                        <PowerOff aria-hidden="true" />
                        Nonaktifkan
                    </DropdownMenuItem>
                ) : (
                    <DropdownMenuItem
                        onSelect={() => onAction('activate', car)}
                    >
                        <Power aria-hidden="true" />
                        Aktifkan
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

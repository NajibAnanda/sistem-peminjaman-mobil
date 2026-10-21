import { Eye, Pencil, Power, PowerOff } from 'lucide-react';
import { RowActions } from '@/components/data-table/row-actions';
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
        <RowActions
            label={`Aksi untuk ${car.plate_number}`}
            actions={[
                { label: 'Lihat detail', icon: Eye, href: show(car.id) },
                {
                    label: 'Edit',
                    icon: Pencil,
                    onSelect: () => onAction('edit', car),
                },
                {
                    label: 'Aktifkan',
                    icon: Power,
                    onSelect: () => onAction('activate', car),
                    hidden: car.is_active,
                },
                {
                    label: 'Nonaktifkan',
                    icon: PowerOff,
                    onSelect: () => onAction('deactivate', car),
                    destructive: true,
                    hidden: !car.is_active,
                },
            ]}
        />
    );
}

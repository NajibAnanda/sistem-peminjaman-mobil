import type { ConfirmActionConfig } from '@/hooks/use-confirm-action';
import { activate, deactivate } from '@/routes/cars';
import type { Car } from '@/types';

/**
 * Teks konfirmasi aktif/nonaktif mobil, dipakai di daftar dan detail mobil.
 */
export function carActivationConfirmation(car: Car): ConfirmActionConfig {
    const carTitle = `${car.brand} ${car.model} (${car.plate_number})`;

    return car.is_active
        ? {
              title: 'Nonaktifkan mobil?',
              description: `${carTitle} tidak dapat dipilih untuk jadwal baru. Riwayat peminjamannya tetap tersimpan, dan mobil dapat diaktifkan kembali.`,
              confirmLabel: 'Nonaktifkan',
              destructive: true,
              route: deactivate(car.id),
          }
        : {
              title: 'Aktifkan kembali mobil?',
              description: `${carTitle} dapat dipilih lagi untuk jadwal baru.`,
              confirmLabel: 'Aktifkan',
              route: activate(car.id),
          };
}

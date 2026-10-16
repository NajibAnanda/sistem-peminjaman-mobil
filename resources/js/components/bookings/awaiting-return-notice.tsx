import { TriangleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    code: string;
};

/**
 * FR-23 (versi ringkas untuk tabel dan daftar): mobil jadwal ini masih
 * dipakai peminjaman Terlambat lain.
 */
export function AwaitingReturnHint({ code }: Props) {
    return (
        // Boleh turun baris agar tidak melebarkan kolom tabel (sel tabel
        // bawaan memakai whitespace-nowrap).
        <span className="inline-flex max-w-44 items-start gap-1 text-xs whitespace-normal text-warning">
            <TriangleAlert
                className="mt-px size-3.5 shrink-0"
                aria-hidden="true"
            />
            <span>Mobil belum kembali dari {code}</span>
        </span>
    );
}

/** FR-23 (versi lengkap untuk halaman detail). */
export function AwaitingReturnAlert({ code }: Props) {
    return (
        <Alert className="border-warning/40 bg-warning/10 text-warning dark:bg-warning/15">
            <TriangleAlert aria-hidden="true" />
            <AlertTitle>Mobil belum kembali</AlertTitle>
            <AlertDescription className="text-foreground">
                Mobil untuk jadwal ini masih dipakai peminjaman {code} yang
                sudah Terlambat. Serah terima ambil baru bisa dilakukan setelah
                mobil dikembalikan. Pertimbangkan memindahkan jadwal ini ke
                mobil lain lewat Edit Jadwal.
            </AlertDescription>
        </Alert>
    );
}

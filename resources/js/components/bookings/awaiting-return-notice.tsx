import { Link } from '@inertiajs/react';
import { Info, TriangleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { show } from '@/routes/bookings';

type HintProps = {
    code: string;
};

type AlertProps = {
    code: string;
    id?: number | null;
};

function BookingLink({ code, id }: { code: string; id?: number | null }) {
    if (!id) {
        return (
            <span className="font-medium text-foreground tabular-nums">
                {code}
            </span>
        );
    }

    return (
        <Link
            href={show(id)}
            className="rounded-sm font-medium text-foreground tabular-nums underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            title={`Buka detail peminjaman ${code}`}
        >
            {code}
        </Link>
    );
}

/**
 * FR-23 (versi ringkas untuk tabel dan daftar): mobil jadwal ini masih
 * dipakai peminjaman Terlambat lain.
 */
export function AwaitingReturnHint({ code }: HintProps) {
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
export function AwaitingReturnAlert({ code, id }: AlertProps) {
    return (
        <Alert className="border-warning/40 bg-warning/10 text-warning dark:bg-warning/15">
            <TriangleAlert aria-hidden="true" />
            <AlertTitle>Mobil belum kembali</AlertTitle>
            <AlertDescription className="text-foreground [&_a]:no-underline [&_a:hover]:underline">
                Mobil untuk jadwal ini masih dipakai peminjaman{' '}
                <BookingLink code={code} id={id} /> yang sudah Terlambat. Serah
                terima ambil baru bisa dilakukan setelah mobil dikembalikan.
                Pertimbangkan memindahkan jadwal ini ke mobil lain lewat Edit
                Jadwal.
            </AlertDescription>
        </Alert>
    );
}

/** Mobil saat ini masih digunakan oleh peminjaman aktif lain (belum kembali). */
export function CarInUseAlert({ code, id }: AlertProps) {
    return (
        <Alert className="border-border bg-muted/50 text-foreground">
            <Info aria-hidden="true" />
            <AlertTitle>Mobil sedang digunakan</AlertTitle>
            <AlertDescription className="text-muted-foreground [&_a]:no-underline [&_a:hover]:underline">
                Mobil untuk jadwal ini saat ini sedang digunakan oleh peminjaman{' '}
                <BookingLink code={code} id={id} />. Serah terima ambil baru
                bisa dilakukan setelah mobil dikembalikan.
            </AlertDescription>
        </Alert>
    );
}

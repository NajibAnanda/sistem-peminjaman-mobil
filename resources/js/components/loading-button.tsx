import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Props = Omit<ComponentProps<typeof Button>, 'asChild'> & {
    /** Hubungkan ke `processing` dari useForm/Form/router Inertia. */
    loading?: boolean;
};

/**
 * Tombol yang memicu request (standar wajib, AGENTS.md 8.3). Saat loading,
 * isi tombol disembunyikan secara visual tetapi tetap mengambil ruang
 * (`invisible`), sehingga ukuran tombol tidak berubah, dan spinner tampil di
 * tengah dengan warna teks tombol. Tanpa `asChild`: tautan tidak punya
 * keadaan loading.
 */
export function LoadingButton({
    loading = false,
    disabled,
    className,
    children,
    ...props
}: Props) {
    return (
        <Button
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={cn('relative', className)}
            {...props}
        >
            {/* `contents`: isi tetap disusun oleh flex tombol itu sendiri (arah,
                jarak, ukuran ikon), sedangkan `invisible` diwariskan ke anak. */}
            <span className={cn('contents', loading && 'invisible')}>
                {children}
            </span>
            {loading && (
                <>
                    <span className="absolute inset-0 flex items-center justify-center">
                        <Spinner
                            aria-hidden="true"
                            aria-label={undefined}
                            role="presentation"
                        />
                    </span>
                    <span className="sr-only">Memproses...</span>
                </>
            )}
        </Button>
    );
}

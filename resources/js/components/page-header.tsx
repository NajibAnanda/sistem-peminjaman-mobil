import type { ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    /** Aksi utama halaman (mis. tombol "Tambah"), di kanan judul. */
    actions?: ReactNode;
};

/**
 * Header halaman daftar: judul + deskripsi di kiri, aksi utama di kanan,
 * di luar dan di atas card tabel (AGENTS.md 8.4).
 */
export function PageHeader({ title, description, actions }: Props) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="space-y-0.5">
                <h1 className="text-xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && <div className="flex shrink-0 gap-2">{actions}</div>}
        </div>
    );
}

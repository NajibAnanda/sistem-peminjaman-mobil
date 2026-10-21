import type { ReactNode } from 'react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Props = {
    title?: string;
    description?: string;
    /** Tombol form (Batal, Simpan); selalu di kanan bawah card. */
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
};

/**
 * Card form standar (AGENTS.md 8.3): selalu selebar area konten, header
 * judul/deskripsi dengan garis bawah, isi dengan padding seragam, dan footer
 * tombol di kanan bawah. Bungkus dengan <form> agar tombol submit di footer
 * ikut mengirim form.
 */
export function FormCard({
    title,
    description,
    footer,
    children,
    className,
}: Props) {
    return (
        <Card className={cn('w-full min-w-0 gap-0 py-0', className)}>
            {title && (
                <div className="border-b border-border px-6 py-4">
                    <h2 className="text-base font-semibold">{title}</h2>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
            )}
            <div className="px-6 py-6">{children}</div>
            {footer && (
                <div className="flex flex-col-reverse gap-2 border-t border-border px-6 py-4 sm:flex-row sm:justify-end">
                    {footer}
                </div>
            )}
        </Card>
    );
}

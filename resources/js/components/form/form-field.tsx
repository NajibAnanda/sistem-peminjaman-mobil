import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = {
    /** id kontrol input, dipakai label (htmlFor) dan petunjuk. */
    id: string;
    label: string;
    /** Tandai field wajib: "*" untuk mata, "(wajib)" untuk pembaca layar. */
    required?: boolean;
    /** Pesan error dari server; tampil di bawah field. */
    error?: string;
    /** Petunjuk singkat di bawah field. id-nya `${id}-hint`. */
    hint?: ReactNode;
    /** Selebar dua kolom di FormGrid. */
    span?: 'full';
    /** Isi tambahan di kanan label, mis. indikator memuat. */
    labelAside?: ReactNode;
    children: ReactNode;
};

/**
 * Satu field form standar: label (dengan penanda wajib), kontrol, petunjuk,
 * lalu error dari server tepat di bawah field.
 */
export function FormField({
    id,
    label,
    required = false,
    error,
    hint,
    span,
    labelAside,
    children,
}: Props) {
    return (
        <div
            className={cn(
                'grid min-w-0 content-start gap-2',
                span === 'full' && '@md/form:col-span-2',
            )}
        >
            <div className="flex min-h-4 items-center justify-between gap-2">
                <Label htmlFor={id}>
                    {label}
                    {required && (
                        <>
                            <span
                                className="text-destructive"
                                aria-hidden="true"
                            >
                                *
                            </span>
                            <span className="sr-only">(wajib)</span>
                        </>
                    )}
                </Label>
                {labelAside}
            </div>
            {children}
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}

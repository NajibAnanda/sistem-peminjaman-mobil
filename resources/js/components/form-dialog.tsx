import type { FormEvent, ReactNode } from 'react';
import { LoadingButton } from '@/components/loading-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    /** Satu kalimat yang menjelaskan isi dialog. */
    description?: ReactNode;
    children: ReactNode;
    /**
     * Dialog form: isi dibungkus <form>, footer bawaan = "Batal" (outline)
     * lalu tombol aksi utama (LoadingButton).
     */
    onSubmit?: (event: FormEvent) => void;
    submitLabel?: string;
    submitVariant?: 'default' | 'destructive';
    processing?: boolean;
    cancelLabel?: string;
    /** data-test tombol aksi utama. */
    submitTestId?: string;
    /** Footer khusus (mengganti footer bawaan); null = tanpa footer. */
    footer?: ReactNode | null;
};

/**
 * Dialog standar untuk semua form dan tampilan rinci (AGENTS.md 8.3): lebar
 * sama (acuan dialog Serah Terima), header + isi + footer dengan padding
 * seragam, tinggi mengikuti layar dengan hanya bagian isi yang di-scroll,
 * dan tidak tertutup saat klik di luar dialog. Selama `processing`, dialog
 * juga tidak bisa ditutup.
 */
export function FormDialog({
    open,
    onOpenChange,
    title,
    description,
    children,
    onSubmit,
    submitLabel = 'Simpan',
    submitVariant = 'default',
    processing = false,
    cancelLabel = 'Batal',
    submitTestId,
    footer,
}: Props) {
    const defaultFooter = onSubmit ? (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
                disabled={processing}
            >
                {cancelLabel}
            </Button>
            <LoadingButton
                type="submit"
                variant={submitVariant}
                loading={processing}
                data-test={submitTestId}
            >
                {submitLabel}
            </LoadingButton>
        </>
    ) : null;
    const footerContent = footer === undefined ? defaultFooter : footer;

    const body = (
        <>
            <DialogHeader className="px-6 pt-6 pr-12 pb-4">
                <DialogTitle>{title}</DialogTitle>
                {description && (
                    <DialogDescription>{description}</DialogDescription>
                )}
            </DialogHeader>
            <div className="min-h-0 flex-1 overflow-y-auto px-6 pt-1 pb-6">
                {children}
            </div>
            {footerContent && (
                <DialogFooter className="m-0 px-6 py-4">
                    {footerContent}
                </DialogFooter>
            )}
        </>
    );

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => !processing && onOpenChange(next)}
        >
            <DialogContent
                className="flex max-h-[calc(100svh-2rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg"
                onInteractOutside={(event) => event.preventDefault()}
                data-test="form-dialog"
            >
                {onSubmit ? (
                    <form
                        onSubmit={onSubmit}
                        className="flex min-h-0 flex-1 flex-col"
                    >
                        {body}
                    </form>
                ) : (
                    body
                )}
            </DialogContent>
        </Dialog>
    );
}

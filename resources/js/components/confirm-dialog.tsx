import type { ReactNode } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { LoadingButton } from '@/components/loading-button';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: ReactNode;
    confirmLabel: string;
    /** Label tombol batal; bawaan "Batal". */
    cancelLabel?: string;
    onConfirm: () => void;
    processing?: boolean;
    destructive?: boolean;
};

/**
 * Konfirmasi untuk aksi berisiko (AGENTS.md 8.3). Tetap terbuka selama
 * permintaan diproses agar pengguna melihat hasilnya.
 */
export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    cancelLabel = 'Batal',
    onConfirm,
    processing = false,
    destructive = false,
}: Props) {
    return (
        <AlertDialog
            open={open}
            onOpenChange={(next) => !processing && onOpenChange(next)}
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        {cancelLabel}
                    </AlertDialogCancel>
                    <LoadingButton
                        variant={destructive ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        loading={processing}
                        data-test="confirm-dialog-button"
                    >
                        {confirmLabel}
                    </LoadingButton>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

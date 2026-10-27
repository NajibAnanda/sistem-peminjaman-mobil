import { Check, Copy } from 'lucide-react';
import { toast } from 'sonner';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { FormDialog } from '@/components/form-dialog';
import { useClipboard } from '@/hooks/use-clipboard';
import type { TemporaryPasswordFlash } from '@/types';

type Props = {
    credentials: TemporaryPasswordFlash | null;
    onClose: () => void;
};

/**
 * Menampilkan password sementara satu kali saja. Setelah ditutup, password
 * tidak dapat dilihat lagi; Pengelola harus mereset ulang bila lupa.
 */
export function TemporaryPasswordDialog({ credentials, onClose }: Props) {
    const [copiedText, copy] = useClipboard();

    if (credentials === null) {
        return null;
    }

    const copied = copiedText === credentials.password;
    const CopyIcon = copied ? Check : Copy;

    const handleCopy = async () => {
        if (!(await copy(credentials.password))) {
            toast.error('Gagal menyalin. Catat password secara manual.');
        }
    };

    return (
        <FormDialog
            open
            onOpenChange={(open) => !open && onClose()}
            title="Password Sementara"
            description={`Berikan password ini kepada ${credentials.name} (${credentials.email}). Password wajib diganti saat login pertama.`}
            footer={<Button onClick={onClose}>Saya sudah mencatatnya</Button>}
        >
            <div className="grid gap-4">
                <div className="flex items-stretch overflow-hidden rounded-lg border border-border">
                    <code
                        className="flex-1 bg-muted px-3 py-2 font-mono text-lg tracking-wider select-all"
                        data-test="temporary-password"
                    >
                        {credentials.password}
                    </code>
                    <button
                        type="button"
                        onClick={handleCopy}
                        className="flex items-center gap-1.5 border-l border-border px-3 text-sm transition-colors hover:bg-muted focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
                    >
                        <CopyIcon className="size-4" aria-hidden="true" />
                        {copied ? 'Tersalin' : 'Salin'}
                    </button>
                </div>

                <Alert>
                    <AlertDescription>
                        Password ini hanya ditampilkan sekali. Setelah dialog
                        ditutup, password tidak dapat dilihat lagi.
                    </AlertDescription>
                </Alert>
            </div>
        </FormDialog>
    );
}

import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowLeft,
    Ban,
    Clock,
    FileQuestion,
    ServerCrash,
    Wrench,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

type Props = {
    status: number;
    title: string;
    description: string;
};

const ICONS: Record<number, LucideIcon> = {
    403: Ban,
    404: FileQuestion,
    429: Clock,
    500: ServerCrash,
    503: Wrench,
};

/**
 * Halaman error ramah (App\Enums\ErrorPageStatus) saat APP_DEBUG=false. Tanpa
 * layout aplikasi agar tetap tampil benar untuk tamu yang belum login.
 */
export default function ErrorPage({ status, title, description }: Props) {
    const Icon = ICONS[status] ?? ServerCrash;

    return (
        <>
            <Head title={title} />

            <main className="flex min-h-svh items-center justify-center bg-background p-6">
                <div className="flex max-w-md flex-col items-center gap-6 text-center">
                    <AppLogoIcon className="size-10" />
                    <div className="flex size-14 items-center justify-center rounded-full bg-muted">
                        <Icon
                            className="size-7 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </div>
                    <div className="space-y-2">
                        <p className="text-sm font-medium text-muted-foreground tabular-nums">
                            Kode {status}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        <p className="text-muted-foreground">{description}</p>
                    </div>
                    <div className="flex flex-wrap justify-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            <ArrowLeft aria-hidden="true" />
                            Kembali
                        </Button>
                        <Button asChild>
                            {/* Untuk URL yang tidak ada, sesi belum dimuat sehingga
                                status login tidak diketahui; halaman utama
                                mengarah ke Dashboard atau ke login sesuai sesi. */}
                            <Link href={home()}>Ke halaman utama</Link>
                        </Button>
                    </div>
                </div>
            </main>
        </>
    );
}

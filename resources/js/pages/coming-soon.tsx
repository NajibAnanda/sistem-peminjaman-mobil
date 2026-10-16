import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';

type Props = {
    title: string;
};

/**
 * Halaman sementara untuk menu yang belum dibangun pada fase ini.
 */
export default function ComingSoon({ title }: Props) {
    return (
        <>
            <Head title={title} />

            <div className="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                    <Construction
                        className="size-6 text-muted-foreground"
                        aria-hidden="true"
                    />
                </div>
                <h1 className="text-lg font-semibold">{title}</h1>
                <p className="max-w-sm text-sm text-muted-foreground">
                    Segera hadir. Halaman ini sedang disiapkan.
                </p>
            </div>
        </>
    );
}

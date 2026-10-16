import { Head, usePage } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        Selamat datang, {auth.user.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Ringkasan armada, jadwal hari ini, serta daftar
                        Terlambat dan Belum Diambil akan tampil di sini.
                    </p>
                </div>
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {[1, 2, 3].map((item) => (
                        <div
                            key={item}
                            className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border"
                        >
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                        </div>
                    ))}
                </div>
                <div className="relative min-h-[50vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border md:min-h-min">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};

import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

type Props = {
    pagination: Pick<Paginated<unknown>, 'links' | 'meta'>;
};

export function PaginationNav({ pagination: { links, meta } }: Props) {
    if (meta.total === 0) {
        return null;
    }

    return (
        <nav
            aria-label="Navigasi halaman"
            className="flex flex-col items-center justify-between gap-3 text-sm text-muted-foreground sm:flex-row"
        >
            <p>
                Menampilkan {meta.from}–{meta.to} dari {meta.total} data
            </p>

            <div className="flex items-center gap-2">
                <PageButton href={links.prev} label="Sebelumnya">
                    <ChevronLeft aria-hidden="true" />
                    Sebelumnya
                </PageButton>
                <span>
                    Halaman {meta.current_page} dari {meta.last_page}
                </span>
                <PageButton href={links.next} label="Berikutnya">
                    Berikutnya
                    <ChevronRight aria-hidden="true" />
                </PageButton>
            </div>
        </nav>
    );
}

function PageButton({
    href,
    label,
    children,
}: {
    href: string | null;
    label: string;
    children: React.ReactNode;
}) {
    if (href === null) {
        return (
            <Button variant="outline" size="sm" disabled aria-label={label}>
                {children}
            </Button>
        );
    }

    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href} preserveScroll preserveState aria-label={label}>
                {children}
            </Link>
        </Button>
    );
}

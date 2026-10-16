import { Download, FileSpreadsheet, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { exportMethod } from '@/routes/history';
import type { Option, TableQueryState } from '@/types';

type Props = {
    /** Pencarian & filter yang berlaku menurut server (= data yang tampil). */
    query: TableQueryState;
    formats: Option<'xlsx' | 'csv'>[];
};

/**
 * Ekspor Riwayat (FR-26). Parameter diambil dari query server, bukan isian
 * yang belum diterapkan, agar isi file selalu sama dengan tabel.
 */
export function ExportMenu({ query, formats }: Props) {
    const href = (format: string) =>
        exportMethod.url({
            query: {
                ...(query.search ? { search: query.search } : {}),
                ...query.filters,
                ...(query.period?.from ? { from: query.period.from } : {}),
                ...(query.period?.to ? { to: query.period.to } : {}),
                format,
            },
        });

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" data-test="export-button">
                    <Download aria-hidden="true" />
                    Ekspor
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel className="font-normal text-muted-foreground">
                    Sesuai pencarian, filter, dan periode yang tampil
                </DropdownMenuLabel>
                {formats.map((format) => (
                    <DropdownMenuItem key={format.value} asChild>
                        {/* Unduhan biasa (bukan kunjungan Inertia). */}
                        <a
                            href={href(format.value)}
                            data-test={`export-${format.value}`}
                        >
                            {format.value === 'xlsx' ? (
                                <FileSpreadsheet aria-hidden="true" />
                            ) : (
                                <FileText aria-hidden="true" />
                            )}
                            {format.label}
                        </a>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

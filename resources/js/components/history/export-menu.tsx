import { ChevronDown, Download, FileSpreadsheet, FileText } from 'lucide-react';
import { LoadingButton } from '@/components/loading-button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useFileDownload } from '@/hooks/use-file-download';
import { exportMethod } from '@/routes/history';
import type { Option, TableQueryState } from '@/types';

type Props = {
    /** Pencarian & filter yang berlaku menurut server (= data yang tampil). */
    query: TableQueryState;
    formats: Option<'xlsx' | 'csv' | 'pdf'>[];
};

/**
 * Ekspor Riwayat (FR-26), di header card tabel di kanan tombol Filter.
 * Parameter diambil dari query server, bukan isian yang belum diterapkan,
 * agar isi file selalu sama dengan tabel. Tombol menampilkan loading sampai
 * file selesai dibuat.
 */
export function ExportMenu({ query, formats }: Props) {
    const { downloading, download } = useFileDownload();

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
                <LoadingButton
                    variant="outline"
                    loading={downloading !== null}
                    data-test="export-button"
                >
                    <Download aria-hidden="true" />
                    Ekspor
                    <ChevronDown aria-hidden="true" />
                </LoadingButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                {formats.map((format) => (
                    <DropdownMenuItem
                        key={format.value}
                        onSelect={() =>
                            void download(
                                format.value,
                                href(format.value),
                                `riwayat-peminjaman.${format.value}`,
                            )
                        }
                        className="whitespace-nowrap"
                        data-test={`export-${format.value}`}
                    >
                        {format.value === 'xlsx' ? (
                            <FileSpreadsheet aria-hidden="true" />
                        ) : (
                            <FileText aria-hidden="true" />
                        )}
                        {format.label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

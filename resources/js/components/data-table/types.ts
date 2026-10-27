import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import type { TableSkeletonKind } from '@/components/skeletons/table-skeleton';
import type { Option } from '@/types';

/**
 * Satu kolom = satu data (AGENTS.md 8.4). Perataan header mengikuti isinya:
 * teks dan tanggal rata kiri (bawaan), angka rata kanan, badge rata tengah
 * (otomatis untuk kolom `skeleton: 'badge'`).
 */
export type DataTableColumn<T> = {
    id: string;
    header: string;
    cell: (row: T) => ReactNode;
    /**
     * 'right' untuk angka (jumlah, durasi, hitungan; otomatis tabular-nums),
     * 'center' untuk badge. Bawaan: 'center' bila `skeleton: 'badge'`, selain
     * itu 'left'.
     */
    align?: 'left' | 'right' | 'center';
    /** Bentuk skeleton saat memuat; bawaan 'number' bila rata kanan, selain itu 'text'. */
    skeleton?: Exclude<TableSkeletonKind, 'index' | 'action'>;
    /** Kelas tambahan untuk sel header dan sel data kolom ini. */
    className?: string;
};

/**
 * Satu grup di dropdown Filter; `key` sama dengan nama parameter query
 * string dan kunci whitelist di TableQuery (server).
 */
export type DataTableFilterGroup = {
    key: string;
    label: string;
    options: Option[];
    /** Tampilkan sebagai submenu bergulir untuk daftar panjang (mis. karyawan). */
    submenu?: boolean;
};

export type DataTableEmptyState = {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
};

import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import type { Option } from '@/types';

export type DataTableColumn<T> = {
    id: string;
    header: string;
    cell: (row: T) => ReactNode;
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
};

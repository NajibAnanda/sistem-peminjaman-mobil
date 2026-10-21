import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { MoreHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type RowAction = {
    label: string;
    icon: LucideIcon;
    /** Tautan halaman; bila tidak ada, `onSelect` dijalankan. */
    href?: NonNullable<InertiaLinkProps['href']>;
    onSelect?: () => void;
    /** Aksi berisiko (nonaktifkan, reset password, batalkan). */
    destructive?: boolean;
    /** Sembunyikan bila tidak berlaku untuk baris ini. */
    hidden?: boolean;
};

type Props = {
    /** Label tombol untuk pembaca layar, mis. "Aksi untuk B 1234 ABC". */
    label: string;
    actions: RowAction[];
};

/**
 * Menu aksi baris standar untuk semua tabel (AGENTS.md 8.4): lebar seragam,
 * setiap item berikon dan satu baris, aksi berisiko selalu di paling bawah
 * setelah pemisah dengan warna destructive.
 */
export function RowActions({ label, actions }: Props) {
    const visible = actions.filter((action) => !action.hidden);
    const regular = visible.filter((action) => !action.destructive);
    const destructive = visible.filter((action) => action.destructive);

    if (visible.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={label}
                    data-test="row-actions"
                >
                    <MoreHorizontal aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-52">
                {regular.map((action) => (
                    <RowActionItem key={action.label} action={action} />
                ))}
                {regular.length > 0 && destructive.length > 0 && (
                    <DropdownMenuSeparator />
                )}
                {destructive.map((action) => (
                    <RowActionItem key={action.label} action={action} />
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function RowActionItem({ action }: { action: RowAction }) {
    const { icon: Icon, label, href, onSelect, destructive } = action;
    const content = (
        <>
            <Icon aria-hidden="true" />
            <span className="truncate">{label}</span>
        </>
    );

    return (
        <DropdownMenuItem
            variant={destructive ? 'destructive' : 'default'}
            className="whitespace-nowrap"
            asChild={href !== undefined}
            onSelect={href === undefined ? onSelect : undefined}
        >
            {href !== undefined ? <Link href={href}>{content}</Link> : content}
        </DropdownMenuItem>
    );
}

import { CalendarIcon, X } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type TriggerProps = ComponentProps<'button'> & {
    /** Nilai terformat, atau null bila kosong. */
    text: string | null;
    placeholder: string;
    invalid?: boolean;
    /** Sisakan ruang untuk tombol hapus (×). */
    clearable?: boolean;
};

/**
 * Tombol pemicu pemilih tanggal yang tampil seperti Input shadcn (tinggi,
 * border, fokus, dan status tidak valid sama), dengan ikon kalender.
 */
export function PickerTrigger({
    text,
    placeholder,
    invalid = false,
    clearable = false,
    className,
    ...props
}: TriggerProps) {
    return (
        <button
            type="button"
            aria-invalid={invalid || undefined}
            className={cn(
                'flex h-8 w-full min-w-0 items-center gap-2 rounded-lg border border-input bg-transparent px-2.5 py-1 text-left text-base transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:bg-input/50 disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 data-[state=open]:border-ring md:text-sm dark:bg-input/30 dark:disabled:bg-input/80 dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40',
                clearable && text !== null && 'pr-8',
                className,
            )}
            {...props}
        >
            <CalendarIcon
                className="size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
            <span
                className={cn(
                    'min-w-0 flex-1 truncate tabular-nums',
                    text === null && 'text-muted-foreground',
                )}
            >
                {text ?? placeholder}
            </span>
        </button>
    );
}

/**
 * Popover pemilih tanggal yang ringkas (±280 px): padding tipis, dan bila
 * ruang di bawah tidak cukup Radix memindahkannya ke atas; isi bisa digulir
 * bila layar terlalu pendek, sehingga popover tidak pernah terpotong.
 */
export const pickerPopoverProps = {
    collisionPadding: 8,
    className:
        'w-auto max-h-(--radix-popover-content-available-height) gap-2.5 overflow-y-auto p-2.5',
} as const;

/** Tombol × di dalam pemicu untuk mengosongkan field opsional. */
export function ClearButton({
    label,
    onClick,
    disabled,
}: {
    label: string;
    onClick: () => void;
    disabled?: boolean;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon-xs"
            className="absolute top-1/2 right-1 -translate-y-1/2 text-muted-foreground"
            aria-label={label}
            title={label}
            onClick={onClick}
            disabled={disabled}
        >
            <X aria-hidden="true" />
        </Button>
    );
}

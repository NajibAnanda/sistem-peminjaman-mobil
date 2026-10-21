import type { LucideIcon } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type Props = {
    icon: LucideIcon;
    /** Keterangan lengkap; tampil di tooltip dan dibaca pembaca layar. */
    text: string;
    tone?: 'warning' | 'muted';
};

const TONES = {
    warning: 'text-warning',
    muted: 'text-muted-foreground',
} as const;

/**
 * Penanda kecil berikon di samping badge status (mis. "Mobil belum kembali"),
 * agar kolom status tetap satu baris. Bisa difokus lewat keyboard.
 */
export function CellNote({ icon: Icon, text, tone = 'warning' }: Props) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className={cn(
                        'inline-flex rounded-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        TONES[tone],
                    )}
                >
                    <Icon className="size-4" aria-hidden="true" />
                    <span className="sr-only">{text}</span>
                </span>
            </TooltipTrigger>
            <TooltipContent>{text}</TooltipContent>
        </Tooltip>
    );
}

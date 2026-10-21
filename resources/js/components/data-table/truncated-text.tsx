import { useRef, useState } from 'react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type Props = {
    text: string;
    /** Batas lebar, mis. "max-w-56"; teks yang lebih panjang dipotong "…". */
    className?: string;
};

/**
 * Teks panjang di sel tabel: dipotong satu baris, dan tooltip berisi teks
 * lengkap hanya muncul bila teksnya benar-benar terpotong. Teks lengkap tetap
 * ada di DOM sehingga terbaca pembaca layar.
 */
export function TruncatedText({ text, className }: Props) {
    const element = useRef<HTMLSpanElement>(null);
    const [open, setOpen] = useState(false);

    const isTruncated = () => {
        const node = element.current;

        return node !== null && node.scrollWidth > node.clientWidth;
    };

    return (
        <Tooltip
            open={open}
            onOpenChange={(next) => setOpen(next && isTruncated())}
        >
            <TooltipTrigger asChild>
                <span ref={element} className={cn('block truncate', className)}>
                    {text}
                </span>
            </TooltipTrigger>
            <TooltipContent className="max-w-sm whitespace-normal">
                {text}
            </TooltipContent>
        </Tooltip>
    );
}

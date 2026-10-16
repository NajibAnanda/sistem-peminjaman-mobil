import { ImageOff } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    label?: string;
    className?: string;
};

export function CarPhotoPlaceholder({ label, className }: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-1 bg-muted text-muted-foreground',
                className,
            )}
        >
            <ImageOff className="size-5" aria-hidden="true" />
            {label && <span className="text-xs">{label}</span>}
        </div>
    );
}

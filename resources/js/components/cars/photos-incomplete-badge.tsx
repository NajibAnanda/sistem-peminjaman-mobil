import { ImageOff } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

/**
 * Penanda mobil yang foto referensinya belum lengkap (AGENTS.md 5.15).
 */
export function PhotosIncompleteBadge() {
    return (
        <Badge
            variant="outline"
            className="border-transparent bg-warning/10 text-warning dark:bg-warning/20"
        >
            <ImageOff aria-hidden="true" />
            Foto belum lengkap
        </Badge>
    );
}

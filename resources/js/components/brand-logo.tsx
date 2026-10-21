import logoUrl from '../../images/brand/pelindo-logo.svg';
import { cn } from '@/lib/utils';

/**
 * Logo penuh Pelindo (ikon + tulisan) dengan warna asli pelindo-source.svg di
 * semua mode, termasuk mode gelap: tidak ada varian warna, filter, atau
 * pewarnaan ulang. Tinggi diatur lewat `className` (mis. "h-9"), lebar
 * mengikuti rasio.
 */
export function BrandLogo({ className }: { className?: string }) {
    return (
        <img
            src={logoUrl}
            alt="Logo Pelindo"
            className={cn('w-auto shrink-0 object-contain', className)}
        />
    );
}

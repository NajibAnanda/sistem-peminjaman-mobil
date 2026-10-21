import iconUrl from '../../images/brand/pelindo-icon.svg';
import { cn } from '@/lib/utils';

/**
 * Ikon "P" Pelindo saja (tanpa tulisan), dari logo vektor resmi. Warnanya
 * tetap biru di mode terang maupun gelap.
 */
export default function AppLogoIcon({ className }: { className?: string }) {
    return (
        <img
            src={iconUrl}
            alt="Logo Pelindo"
            className={cn('shrink-0 object-contain', className)}
        />
    );
}

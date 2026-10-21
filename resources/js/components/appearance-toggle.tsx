import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';

/**
 * Satu klik berganti terang ⇄ gelap. Ikon menunjukkan mode yang sedang tampil
 * (termasuk saat pengaturannya "Ikuti Sistem"); klik pertama dari "Ikuti
 * Sistem" menjadi pilihan eksplisit kebalikannya. "Ikuti Sistem" tetap ada di
 * Pengaturan → Tampilan. Memakai mekanisme appearance starter kit sehingga
 * tersimpan di browser dan sinkron dengan halaman Pengaturan.
 */
export function AppearanceToggle() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';
    const label = isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap';

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
                    aria-label={label}
                    data-test="appearance-toggle"
                >
                    {/* Kedua ikon ditumpuk; kelas dark: mengikuti <html class="dark">
                        sehingga ikon langsung benar sejak halaman dimuat. */}
                    <span className="relative size-4" aria-hidden="true">
                        <Sun className="absolute inset-0 scale-100 rotate-0 transition-transform duration-300 dark:scale-0 dark:-rotate-90" />
                        <Moon className="absolute inset-0 scale-0 rotate-90 transition-transform duration-300 dark:scale-100 dark:rotate-0" />
                    </span>
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

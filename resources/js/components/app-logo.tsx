import AppLogoIcon from '@/components/app-logo-icon';
import { BrandLogo } from '@/components/brand-logo';

/**
 * Judul sidebar: logo penuh (tinggi 36px, di tengah) saat sidebar terbuka,
 * ikon "P" saja (24px, utuh dan di tengah tombol) saat sidebar diciutkan.
 */
export default function AppLogo() {
    return (
        <>
            <BrandLogo className="h-9 group-data-[collapsible=icon]:hidden" />
            <AppLogoIcon className="hidden size-6 group-data-[collapsible=icon]:block" />
        </>
    );
}

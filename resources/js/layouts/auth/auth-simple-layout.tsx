import { AppearanceToggle } from '@/components/appearance-toggle';
import { BrandLogo } from '@/components/brand-logo';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { AuthLayoutProps } from '@/types';

/**
 * Bingkai semua halaman autentikasi (login, ganti password sementara,
 * konfirmasi password, 2FA): satu card di tengah berisi logo Pelindo, judul,
 * dan form; nama perusahaan di bawah card; pengubah tema di pojok kanan atas.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-3 bg-background p-6 md:p-10">
            <div className="absolute top-4 right-4">
                <AppearanceToggle />
            </div>

            <Card className="w-full max-w-sm">
                <CardHeader className="justify-items-center text-center">
                    <BrandLogo className="mb-4 h-10" />
                    {title && (
                        <CardTitle className="text-xl">
                            <h1>{title}</h1>
                        </CardTitle>
                    )}
                    {description && (
                        <CardDescription>{description}</CardDescription>
                    )}
                </CardHeader>
                <CardContent>{children}</CardContent>
            </Card>

            <p className="text-center text-xs text-muted-foreground">
                PT Pelabuhan Indonesia (Persero)
            </p>
        </div>
    );
}

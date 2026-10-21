import type { ReactNode } from 'react';

/**
 * Tata letak field form: 2 kolom bila wadahnya ≥ 448px (termasuk dialog form
 * standar), 1 kolom bila lebih sempit.
 * Memakai container query (lebar card/dialog), bukan lebar layar, karena
 * sidebar dan dialog ikut menentukan ruang yang tersedia. Field selebar
 * penuh memakai `span="full"` di FormField.
 */
export function FormGrid({ children }: { children: ReactNode }) {
    return (
        <div className="@container/form">
            <div className="grid gap-x-6 gap-y-5 @md/form:grid-cols-2">
                {children}
            </div>
        </div>
    );
}

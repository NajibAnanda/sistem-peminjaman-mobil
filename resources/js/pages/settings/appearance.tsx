import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import { FormCard } from '@/components/form/form-card';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';

export default function Appearance() {
    return (
        <>
            <Head title="Pengaturan tampilan" />

            <FormCard
                title="Tampilan"
                description="Pilih tema terang, gelap, atau mengikuti sistem. Pilihan disimpan di peramban ini."
            >
                <AppearanceTabs />
            </FormCard>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan',
            href: editProfile(),
        },
        {
            title: 'Tampilan',
            href: editAppearance(),
        },
    ],
};

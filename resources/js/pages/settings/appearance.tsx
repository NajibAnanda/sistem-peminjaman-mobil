import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import { FormCard } from '@/components/form/form-card';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Pengaturan tampilan" />

            <FormCard
                title="Tampilan"
                description="Pilih tampilan terang, gelap, atau mengikuti pengaturan sistem; pilihan disimpan di browser ini."
            >
                <AppearanceTabs />
            </FormCard>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan tampilan',
            href: editAppearance(),
        },
    ],
};

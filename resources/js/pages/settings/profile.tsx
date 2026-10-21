import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { FormCard } from '@/components/form/form-card';
import { FormField } from '@/components/form/form-field';
import { FormGrid } from '@/components/form/form-grid';
import { LoadingButton } from '@/components/loading-button';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/profile';

export default function Profile() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Pengaturan profil" />

            <Form
                {...ProfileController.update.form()}
                options={{
                    preserveScroll: true,
                }}
            >
                {({ processing, errors }) => (
                    <FormCard
                        title="Profil"
                        description="Perbarui nama yang tampil di aplikasi; email hanya dapat diubah oleh Pengelola."
                        footer={
                            <LoadingButton
                                loading={processing}
                                data-test="update-profile-button"
                            >
                                Simpan
                            </LoadingButton>
                        }
                    >
                        <FormGrid>
                            <FormField
                                id="name"
                                label="Nama"
                                required
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    maxLength={255}
                                    autoComplete="name"
                                    placeholder="Nama lengkap"
                                    aria-invalid={!!errors.name || undefined}
                                />
                            </FormField>

                            <FormField
                                id="email"
                                label="Email"
                                hint="Email hanya dapat diubah oleh Pengelola."
                            >
                                {/* Tanpa atribut name: email tidak dikirim dan
                                    hanya dapat diubah Pengelola (FR-03). */}
                                <Input
                                    id="email"
                                    type="email"
                                    value={auth.user.email}
                                    readOnly
                                    disabled
                                    autoComplete="username"
                                    aria-describedby="email-hint"
                                />
                            </FormField>
                        </FormGrid>
                    </FormCard>
                )}
            </Form>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan profil',
            href: edit(),
        },
    ],
};

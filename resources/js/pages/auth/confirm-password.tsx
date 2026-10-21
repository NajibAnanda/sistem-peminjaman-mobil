import { Form, Head } from '@inertiajs/react';
import { FormField } from '@/components/form/form-field';
import PasswordInput from '@/components/password-input';
import { LoadingButton } from '@/components/loading-button';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Konfirmasi password" />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <FormField
                            id="password"
                            label="Password"
                            required
                            error={errors.password}
                        >
                            <PasswordInput
                                required
                                id="password"
                                name="password"
                                placeholder="Password"
                                autoComplete="current-password"
                                autoFocus
                            />
                        </FormField>

                        <div className="flex items-center">
                            <LoadingButton
                                className="w-full"
                                loading={processing}
                                data-test="confirm-password-button"
                            >
                                Konfirmasi password
                            </LoadingButton>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Konfirmasi password',
    description:
        'Masukkan password Anda untuk melanjutkan ke pengaturan keamanan akun.',
};

import { Form, Head } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { FormCard } from '@/components/form/form-card';
import { FormField } from '@/components/form/form-field';
import { FormGrid } from '@/components/form/form-grid';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { LoadingButton } from '@/components/loading-button';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
    twoFactorRequired: boolean;
} & ManageTwoFactorProps;

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Pengaturan keamanan" />

            {props.twoFactorRequired && !props.twoFactorEnabled && (
                <Alert>
                    <ShieldAlert />
                    <AlertTitle>Autentikasi dua faktor wajib</AlertTitle>
                    <AlertDescription>
                        Akun Pengelola wajib mengaktifkan autentikasi dua faktor
                        sebelum dapat memakai aplikasi.
                    </AlertDescription>
                </Alert>
            )}

            <Form
                {...SecurityController.update.form()}
                options={{
                    preserveScroll: true,
                }}
                resetOnError={[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]}
                resetOnSuccess
                onError={(errors) => {
                    if (errors.password) {
                        passwordInput.current?.focus();
                    }

                    if (errors.current_password) {
                        currentPasswordInput.current?.focus();
                    }
                }}
            >
                {({ errors, processing }) => (
                    <FormCard
                        title="Ganti password"
                        description="Gunakan minimal 10 karakter yang berisi huruf dan angka, dan jangan dipakai di aplikasi lain."
                        footer={
                            <LoadingButton
                                loading={processing}
                                data-test="update-password-button"
                            >
                                Simpan
                            </LoadingButton>
                        }
                    >
                        <FormGrid>
                            <FormField
                                id="current_password"
                                label="Password saat ini"
                                required
                                error={errors.current_password}
                                span="full"
                            >
                                <PasswordInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    required
                                    autoComplete="current-password"
                                    placeholder="Password saat ini"
                                />
                            </FormField>

                            <FormField
                                id="password"
                                label="Password baru"
                                required
                                error={errors.password}
                            >
                                <PasswordInput
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    required
                                    autoComplete="new-password"
                                    placeholder="Password baru"
                                    passwordrules={props.passwordRules}
                                />
                            </FormField>

                            <FormField
                                id="password_confirmation"
                                label="Ulangi password baru"
                                required
                                error={errors.password_confirmation}
                            >
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    placeholder="Ulangi password baru"
                                    passwordrules={props.passwordRules}
                                />
                            </FormField>
                        </FormGrid>
                    </FormCard>
                )}
            </Form>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan keamanan',
            href: edit(),
        },
    ],
};

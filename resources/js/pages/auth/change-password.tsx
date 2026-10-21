import { Form, Head, Link } from '@inertiajs/react';
import { FormField } from '@/components/form/form-field';
import PasswordInput from '@/components/password-input';
import { LoadingButton } from '@/components/loading-button';
import { logout } from '@/routes';
import { update } from '@/routes/password/change';

type Props = {
    passwordRules: string;
};

export default function ChangePassword({ passwordRules }: Props) {
    return (
        <>
            <Head title="Ganti password" />

            <Form
                {...update.form()}
                resetOnError
                resetOnSuccess
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <FormField
                            id="current_password"
                            label="Password sementara"
                            required
                            error={errors.current_password}
                        >
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                required
                                autoFocus
                                autoComplete="current-password"
                                placeholder="Password dari Pengelola"
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
                                name="password"
                                required
                                autoComplete="new-password"
                                placeholder="Minimal 10 karakter, huruf dan angka"
                                passwordrules={passwordRules}
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
                                passwordrules={passwordRules}
                            />
                        </FormField>

                        <LoadingButton
                            type="submit"
                            className="w-full"
                            loading={processing}
                            data-test="change-password-button"
                        >
                            Simpan password baru
                        </LoadingButton>

                        <Link
                            href={logout()}
                            as="button"
                            className="text-center text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                        >
                            Keluar
                        </Link>
                    </div>
                )}
            </Form>
        </>
    );
}

ChangePassword.layout = {
    title: 'Ganti password sementara',
    description:
        'Demi keamanan, ganti password sementara dari Pengelola sebelum memakai aplikasi.',
};

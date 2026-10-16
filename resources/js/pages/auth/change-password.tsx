import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">
                                Password sementara
                            </Label>
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                required
                                autoFocus
                                autoComplete="current-password"
                                placeholder="Password dari Pengelola"
                            />
                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password baru</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="new-password"
                                placeholder="Minimal 10 karakter, huruf dan angka"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Ulangi password baru
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                                placeholder="Ulangi password baru"
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="change-password-button"
                        >
                            {processing && <Spinner />}
                            Simpan password baru
                        </Button>

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

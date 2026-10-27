import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { LoadingButton } from '@/components/loading-button';
import PasswordInput from '@/components/password-input';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/login';

type Props = {
    status?: string;
};

export default function Login({ status }: Props) {
    return (
        <>
            <Head title="Masuk" />

            <div className="grid gap-6">
                {status && (
                    <div className="text-center text-sm font-medium text-success">
                        {status}
                    </div>
                )}

                <Form
                    {...store.form()}
                    resetOnSuccess={['password']}
                    className="grid gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    autoCapitalize="none"
                                    spellCheck={false}
                                    placeholder="nama@perusahaan.co.id"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox id="remember" name="remember" />
                                <Label htmlFor="remember">Ingat saya</Label>
                            </div>

                            <LoadingButton
                                type="submit"
                                className="w-full"
                                loading={processing}
                                data-test="login-button"
                            >
                                Masuk
                            </LoadingButton>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Login.layout = {
    title: 'Sistem Peminjaman Mobil',
    description: 'Masukkan Email dan Password Anda untuk mengakses sistem.',
};

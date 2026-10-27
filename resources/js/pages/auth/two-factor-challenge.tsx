import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useEffect, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { LoadingButton } from '@/components/loading-button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Kode pemulihan',
                description:
                    'Masukkan salah satu kode pemulihan darurat Anda untuk masuk.',
                toggleText: 'masuk dengan kode autentikasi',
            };
        }

        return {
            title: 'Kode autentikasi',
            description:
                'Masukkan kode 6 digit dari aplikasi autentikator di ponsel Anda.',
            toggleText: 'masuk dengan kode pemulihan',
        };
    }, [showRecoveryInput]);

    useEffect(() => {
        setLayoutProps({
            title: authConfigContent.title,
            description: authConfigContent.description,
        });
    }, [authConfigContent]);

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title="Autentikasi dua faktor" />

            <div className="space-y-6">
                <Form
                    {...store.form()}
                    className="space-y-4"
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="recovery_code">
                                        Kode pemulihan
                                    </Label>
                                    <Input
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        placeholder="Masukkan kode pemulihan"
                                        autoFocus={showRecoveryInput}
                                        required
                                    />
                                    <InputError
                                        message={errors.recovery_code}
                                    />
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
                                            disabled={processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                            autoFocus
                                        >
                                            <InputOTPGroup>
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot
                                                            key={index}
                                                            index={index}
                                                        />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError message={errors.code} />
                                </div>
                            )}

                            <LoadingButton
                                type="submit"
                                className="w-full"
                                loading={processing}
                            >
                                Lanjutkan
                            </LoadingButton>

                            <div className="text-center text-sm text-muted-foreground">
                                <span>atau </span>
                                <button
                                    type="button"
                                    className="cursor-pointer text-foreground underline decoration-border underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                    onClick={() =>
                                        toggleRecoveryMode(clearErrors)
                                    }
                                >
                                    {authConfigContent.toggleText}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

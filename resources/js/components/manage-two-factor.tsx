import { Form } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormCard } from '@/components/form/form-card';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { LoadingButton } from '@/components/loading-button';
import { Button } from '@/components/ui/button';
import { useConfirmAction } from '@/hooks/use-confirm-action';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

export default function ManageTwoFactor(props: Props) {
    const requiresConfirmation = props.requiresConfirmation ?? false;
    const twoFactorEnabled = props.twoFactorEnabled ?? false;

    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const prevTwoFactorEnabled = useRef(twoFactorEnabled);
    const confirmation = useConfirmAction();

    useEffect(() => {
        if (prevTwoFactorEnabled.current && !twoFactorEnabled) {
            clearTwoFactorAuthData();
        }

        prevTwoFactorEnabled.current = twoFactorEnabled;
    }, [twoFactorEnabled, clearTwoFactorAuthData]);

    if (!(props.canManageTwoFactor ?? false)) {
        return null;
    }

    return (
        <FormCard
            title="Autentikasi dua faktor"
            description="Lapisan keamanan tambahan yang meminta kode dari ponsel Anda setiap kali masuk."
        >
            {twoFactorEnabled ? (
                <div className="flex flex-col items-start justify-start space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Saat masuk, Anda akan diminta kode 6 digit dari aplikasi
                        autentikator di ponsel Anda.
                    </p>

                    <div>
                        <Button
                            variant="destructive"
                            type="button"
                            onClick={() =>
                                confirmation.request({
                                    title: 'Nonaktifkan 2FA?',
                                    description:
                                        'Keamanan akun Anda akan berkurang dan Anda tidak lagi diminta kode verifikasi dari ponsel saat masuk.',
                                    confirmLabel: 'Nonaktifkan 2FA',
                                    destructive: true,
                                    route: disable(),
                                })
                            }
                        >
                            Nonaktifkan 2FA
                        </Button>
                    </div>

                    <TwoFactorRecoveryCodes
                        recoveryCodesList={recoveryCodesList}
                        fetchRecoveryCodes={fetchRecoveryCodes}
                        errors={errors}
                    />
                </div>
            ) : (
                <div className="flex flex-col items-start justify-start space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Setelah diaktifkan, setiap kali masuk Anda akan diminta
                        kode 6 digit dari aplikasi autentikator di ponsel
                        (misalnya Google Authenticator).
                    </p>

                    <div>
                        {hasSetupData ? (
                            <Button onClick={() => setShowSetupModal(true)}>
                                <ShieldCheck
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Lanjutkan pengaturan
                            </Button>
                        ) : (
                            <Form
                                {...enable.form()}
                                onSuccess={() => setShowSetupModal(true)}
                            >
                                {({ processing }) => (
                                    <LoadingButton
                                        type="submit"
                                        loading={processing}
                                    >
                                        Aktifkan 2FA
                                    </LoadingButton>
                                )}
                            </Form>
                        )}
                    </div>
                </div>
            )}

            <TwoFactorSetupModal
                isOpen={showSetupModal}
                onClose={() => setShowSetupModal(false)}
                requiresConfirmation={requiresConfirmation}
                twoFactorEnabled={twoFactorEnabled}
                qrCodeSvg={qrCodeSvg}
                manualSetupKey={manualSetupKey}
                clearSetupData={clearSetupData}
                fetchSetupData={fetchSetupData}
                errors={errors}
            />

            <ConfirmDialog {...confirmation.dialogProps} />
        </FormCard>
    );
}

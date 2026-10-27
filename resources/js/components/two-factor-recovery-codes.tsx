import { router } from '@inertiajs/react';
import { Eye, EyeOff, LockKeyhole, RefreshCw } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import AlertError from '@/components/alert-error';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { LoadingButton } from '@/components/loading-button';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

type Props = {
    recoveryCodesList: string[];
    fetchRecoveryCodes: () => Promise<void>;
    errors: string[];
};

export default function TwoFactorRecoveryCodes({
    recoveryCodesList,
    fetchRecoveryCodes,
    errors,
}: Props) {
    const [codesAreVisible, setCodesAreVisible] = useState<boolean>(false);
    const [fetchingCodes, setFetchingCodes] = useState(false);
    const [confirmRegenerateOpen, setConfirmRegenerateOpen] = useState(false);
    const [regenerating, setRegenerating] = useState(false);
    const codesSectionRef = useRef<HTMLDivElement | null>(null);
    const canRegenerateCodes = recoveryCodesList.length > 0 && codesAreVisible;

    const toggleCodesVisibility = useCallback(async () => {
        if (!codesAreVisible && !recoveryCodesList.length) {
            setFetchingCodes(true);

            try {
                await fetchRecoveryCodes();
            } finally {
                setFetchingCodes(false);
            }
        }

        setCodesAreVisible(!codesAreVisible);

        if (!codesAreVisible) {
            setTimeout(() => {
                codesSectionRef.current?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            });
        }
    }, [codesAreVisible, recoveryCodesList.length, fetchRecoveryCodes]);

    const handleRegenerate = useCallback(() => {
        router.post(
            regenerateRecoveryCodes.url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setRegenerating(true),
                onSuccess: async () => {
                    await fetchRecoveryCodes();
                    setConfirmRegenerateOpen(false);
                },
                onFinish: () => setRegenerating(false),
            },
        );
    }, [fetchRecoveryCodes]);

    const RecoveryCodeIconComponent = codesAreVisible ? EyeOff : Eye;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex gap-3">
                    <LockKeyhole className="size-4" aria-hidden="true" />
                    Kode pemulihan 2FA
                </CardTitle>
                <CardDescription>
                    Kode pemulihan dipakai untuk masuk bila ponsel autentikator
                    hilang. Simpan di tempat yang aman.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between">
                    <LoadingButton
                        onClick={toggleCodesVisibility}
                        className="w-fit"
                        loading={fetchingCodes}
                        aria-expanded={codesAreVisible}
                        aria-controls="recovery-codes-section"
                    >
                        <RecoveryCodeIconComponent
                            className="size-4"
                            aria-hidden="true"
                        />
                        {codesAreVisible ? 'Sembunyikan' : 'Lihat'} kode
                        pemulihan
                    </LoadingButton>

                    {canRegenerateCodes && (
                        <Button
                            variant="secondary"
                            type="button"
                            onClick={() => setConfirmRegenerateOpen(true)}
                            aria-describedby="regenerate-warning"
                        >
                            <RefreshCw className="size-4" aria-hidden="true" />{' '}
                            Buat ulang kode
                        </Button>
                    )}
                </div>
                <div
                    id="recovery-codes-section"
                    className={`relative overflow-hidden transition-opacity duration-300 ${codesAreVisible ? 'h-auto opacity-100' : 'h-0 opacity-0'}`}
                    aria-hidden={!codesAreVisible}
                >
                    <div className="mt-3 space-y-3">
                        {errors?.length ? (
                            <AlertError errors={errors} />
                        ) : (
                            <>
                                <div
                                    ref={codesSectionRef}
                                    className="grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm"
                                    role="list"
                                    aria-label="Kode pemulihan"
                                >
                                    {recoveryCodesList.length ? (
                                        recoveryCodesList.map((code) => (
                                            <div
                                                key={code}
                                                role="listitem"
                                                className="select-text"
                                            >
                                                {code}
                                            </div>
                                        ))
                                    ) : (
                                        <div
                                            className="space-y-2"
                                            aria-label="Memuat kode pemulihan"
                                        >
                                            {Array.from(
                                                { length: 8 },
                                                (_, index) => (
                                                    <div
                                                        key={index}
                                                        className="h-4 animate-pulse rounded bg-muted-foreground/20"
                                                        aria-hidden="true"
                                                    />
                                                ),
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div className="text-xs text-muted-foreground select-none">
                                    <p id="regenerate-warning">
                                        Setiap kode pemulihan hanya bisa dipakai
                                        sekali. Bila perlu kode baru, klik{' '}
                                        <span className="font-bold">
                                            Buat ulang kode
                                        </span>{' '}
                                        di atas.
                                    </p>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </CardContent>

            <ConfirmDialog
                open={confirmRegenerateOpen}
                onOpenChange={setConfirmRegenerateOpen}
                title="Buat ulang kode pemulihan?"
                description="Semua kode pemulihan sebelumnya tidak akan berlaku lagi setelah kode baru dibuat. Pastikan Anda menyimpan kode baru di tempat yang aman."
                confirmLabel="Buat ulang kode"
                onConfirm={handleRegenerate}
                processing={regenerating}
            />
        </Card>
    );
}

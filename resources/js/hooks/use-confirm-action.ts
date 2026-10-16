import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';

export type ConfirmActionConfig = {
    title: string;
    description: ReactNode;
    confirmLabel: string;
    destructive?: boolean;
    route: UrlMethodPair;
    data?: Record<string, string>;
};

export type UseConfirmActionReturn = {
    /** Membuka dialog konfirmasi untuk aksi berisiko (AGENTS.md 8.3). */
    request: (config: ConfirmActionConfig) => void;
    dialogProps: {
        open: boolean;
        onOpenChange: (open: boolean) => void;
        title: string;
        description: ReactNode;
        confirmLabel: string;
        destructive: boolean;
        processing: boolean;
        onConfirm: () => void;
    };
};

export function useConfirmAction(): UseConfirmActionReturn {
    const [config, setConfig] = useState<ConfirmActionConfig | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (config === null) {
            return;
        }

        router.visit(config.route, {
            data: config.data,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfig(null);
            },
        });
    };

    return {
        request: setConfig,
        dialogProps: {
            open: config !== null,
            onOpenChange: (open) => !open && !processing && setConfig(null),
            title: config?.title ?? '',
            description: config?.description ?? '',
            confirmLabel: config?.confirmLabel ?? '',
            destructive: config?.destructive ?? false,
            processing,
            onConfirm: confirm,
        },
    };
}

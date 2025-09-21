import { router } from '@inertiajs/vue3';

export interface TwoFactorResendDeps {
    suspended: () => boolean;
    allowed: () => boolean;
    setError: (msg: string) => void;
    setWait: (s: number) => void;
    setAllowed: (v: boolean) => void;
    reload: () => void; // triggers inertia partial reload
}

export const useTwoFactorResend = (deps: TwoFactorResendDeps) => {
    const resend = () => {
        if (deps.suspended() || !deps.allowed()) return;
        router.post(
            route('2fa.resend'),
            {},
            {
                onSuccess: () => {
                    deps.reload();
                },
                onError: (errs) => {
                    const msg = (errs.resend as string) || 'Cannot resend now.';
                    deps.setError(msg);
                    const match = msg.match(/wait\s+(\d+)s/i);
                    if (match) {
                        const s = parseInt(match[1], 10);
                        if (!isNaN(s) && s > 0) {
                            deps.setWait(s);
                            deps.setAllowed(false);
                        }
                    }
                    deps.reload();
                },
            },
        );
    };
    return { resend };
};

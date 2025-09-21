import { onBeforeUnmount, ref, watch } from 'vue';

export interface ResendWindowArgs {
    nextResendAt: () => string | null | undefined;
    waitSeconds: () => number | undefined;
    suspended: () => boolean | undefined;
    allowed: () => boolean | undefined;
}

export interface ResendWindowAPI {
    resendWait: ReturnType<typeof ref<number>>;
    resendAllowed: ReturnType<typeof ref<boolean>>;
    resendSuspended: ReturnType<typeof ref<boolean>>;
    nextAt: ReturnType<typeof ref<Date | null>>;
    formatCompact: (seconds: number) => string;
}

export const useResendWindow = (arg: ResendWindowArgs): ResendWindowAPI => {
    const resendWait = ref(0);
    const resendAllowed = ref(true);
    const resendSuspended = ref(false);
    const nextAt = ref<Date | null>(null);
    let interval: ReturnType<typeof setInterval> | null = null;

    const clear = () => {
        if (interval) {
            clearInterval(interval);
            interval = null;
        }
    };

    const hydrate = () => {
        resendSuspended.value = !!arg.suspended();
        const next = arg.nextResendAt();
        nextAt.value = next ? new Date(next) : null;

        if (nextAt.value) {
            const diff = Math.ceil((nextAt.value.getTime() - Date.now()) / 1000);
            resendWait.value = diff > 0 ? diff : 0;
        } else {
            resendWait.value = arg.waitSeconds() ?? 0;
        }

        resendAllowed.value = !!arg.allowed() && resendWait.value === 0 && !resendSuspended.value;

        clear();
        if (!resendSuspended.value && resendWait.value > 0) {
            interval = setInterval(() => {
                if (nextAt.value) {
                    const d = Math.ceil((nextAt.value.getTime() - Date.now()) / 1000);
                    resendWait.value = d > 0 ? d : 0;
                } else if (resendWait.value > 0) {
                    resendWait.value -= 1;
                }
                if (resendWait.value <= 0) {
                    resendAllowed.value = !resendSuspended.value;
                    clear();
                }
            }, 1000);
        }
    };

    watch(() => [arg.nextResendAt(), arg.waitSeconds(), arg.allowed(), arg.suspended()], hydrate, { immediate: true });

    onBeforeUnmount(clear);

    const formatCompact = (seconds: number): string => {
        if (seconds <= 0) return '0s';
        if (seconds < 60) return `${seconds}s`;
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return h > 0 ? `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}` : `${m}:${s.toString().padStart(2, '0')}`;
    };

    return { resendWait, resendAllowed, resendSuspended, nextAt, formatCompact };
};

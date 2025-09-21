import { computed } from 'vue';

export const useTwoFactorAttempts = (
    attemptCount: () => number | undefined,
    maxBeforeSuspend: () => number | undefined,
    suspended: () => boolean,
) => {
    const attemptLabel = computed(() => {
        const c = attemptCount();
        const max = maxBeforeSuspend();
        if (typeof c !== 'number' || typeof max !== 'number') return '';
        return `Attempt ${c + 1} of ${max}`;
    });

    const showAttemptLabel = computed(() => !!attemptLabel.value && !suspended());

    const warningLabel = computed(() => {
        if (suspended()) return '';
        const c = attemptCount();
        const max = maxBeforeSuspend();
        if (typeof c !== 'number' || typeof max !== 'number') return '';
        const remaining = max - c;
        if (remaining > 2 || c >= max) return '';
        return `After ${max} attempts your account will be temporarily locked.`;
    });

    const showWarning = computed(() => warningLabel.value.length > 0);

    return { attemptLabel, showAttemptLabel, warningLabel, showWarning };
};

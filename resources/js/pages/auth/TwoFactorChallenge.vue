<script setup lang="ts">
import { PinInput, PinInputGroup, PinInputSeparator, PinInputSlot } from '@/components/ui/pin-input';
import { useCountdown } from '@/composables/useCountdown';
import { usePendingActionLabel } from '@/composables/usePendingActionLabel';
import { useResendWindow } from '@/composables/useResendWindow';
import { useTwoFactorAttempts } from '@/composables/useTwoFactorAttempts';
import { useTwoFactorResend } from '@/composables/useTwoFactorResend';
import type { TwoFactorChallengeProps } from '@/interfaces/twofactor';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<TwoFactorChallengeProps>();
const { t } = useI18n();

// Constants
const CODE_LENGTH = 6;
const RESEND_REFRESH_KEYS: Array<keyof TwoFactorChallengeProps> = ['resend_wait_seconds', 'next_resend_at', 'resend_allowed', 'resend_suspended'];

// Countdown for challenge expiration
const { remaining, start: startCountdown, isExpired, mmss } = useCountdown(props.remaining_seconds ?? 0);
// Resend window control
const resendWindow = useResendWindow({
    nextResendAt: () => props.next_resend_at,
    waitSeconds: () => props.resend_wait_seconds,
    suspended: () => props.resend_suspended,
    allowed: () => props.resend_allowed,
});
// Attempt labels
const attempts = useTwoFactorAttempts(
    () => props.resend_attempt_count,
    () => props.resend_max_before_suspend,
    () => !!resendWindow.resendSuspended.value,
);
// Pending action label
const { pendingActionLabel } = usePendingActionLabel(() => props.pending_action);

// Form / state
const form = useForm({ code: '', trust: true });
const values = ref<string[]>(Array(CODE_LENGTH).fill(''));
const submitting = ref(false);
const error = ref<string | null>(null);
const resentFlag = ref(props.resent);

// Submission
const submit = (): void => {
    if (submitting.value) return;
    submitting.value = true;
    form.post(route('2fa.verify'), {
        preserveScroll: true,
        onError: (errs) => {
            error.value = (errs.code as string) || 'Invalid code';
            submitting.value = false;
            values.value = Array(CODE_LENGTH).fill('');
        },
        onSuccess: () => {
            submitting.value = false;
        },
    });
};

// Resend handler via composable
const resend = (): void => resendComposable.resend();
const resendComposable = useTwoFactorResend({
    suspended: () => !!resendWindow.resendSuspended.value,
    allowed: () => !!resendWindow.resendAllowed.value,
    setError: (m: string) => {
        error.value = m;
    },
    setWait: (s: number) => {
        resendWindow.resendWait.value = s;
    },
    setAllowed: (v: boolean) => {
        resendWindow.resendAllowed.value = v;
    },
    reload: () =>
        router.reload({
            only: ['expires_at', 'remaining_seconds', 'resent', 'resend_allowed', 'resend_wait_seconds', 'resend_suspended', 'next_resend_at'],
        }),
});

const cancel = (): void => {
    router.post(route('2fa.cancel'));
};

// Watch PIN values to auto-submit
watch(values, (v) => {
    if (isExpired()) return;
    form.code = v.join('').toUpperCase();
    error.value = null;
    if (form.code.length === CODE_LENGTH) submit();
});

// React to challenge refresh
watch(
    () => props.remaining_seconds,
    (val) => {
        if (typeof val === 'number' && val > 0) {
            remaining.value = val; // direct adjust
            values.value = Array(CODE_LENGTH).fill('');
            error.value = null;
            resentFlag.value = props.resent;
            startCountdown(val);
        }
    },
);

// React to resend-related prop changes (trigger internal recompute)
watch(
    () => RESEND_REFRESH_KEYS.map((k) => props[k]),
    () => {
        /* hydrated internally by useResendWindow watcher */
    },
    { immediate: true },
);

watch(
    () => props.resent,
    (val) => {
        resentFlag.value = val;
    },
);
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <Head title="Two-Factor" />
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-xl font-semibold tracking-tight">{{ t('security.2fa.heading') || t('security.2fa.title') }}</h1>
                <p class="text-sm text-muted-foreground" v-if="!isExpired()">{{ t('security.2fa.instructions') || t('security.2fa.description') }}</p>
                <p class="text-sm text-destructive" v-else>{{ t('security.2fa.expired') }}</p>
                <p v-if="resentFlag" class="text-xs text-emerald-600">{{ t('security.2fa.new_code_sent') }}</p>
                <p v-if="!isExpired()" class="text-xs">{{ t('security.2fa.expires_in', { time: mmss() }) }}</p>
                <div v-if="props.pending_action" class="mt-2 rounded bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700">
                    {{ t('security.2fa.pending_confirm') }} {{ pendingActionLabel }}
                </div>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="flex flex-col items-center gap-4">
                    <PinInput v-model="values" :otp="true" :disabled="submitting || isExpired()" class="flex">
                        <PinInputGroup class="flex items-center">
                            <template v-for="(val, idx) in values" :key="idx">
                                <PinInputSlot :index="idx" class="h-12 w-10 text-lg font-semibold" />
                                <PinInputSeparator v-if="idx < values.length - 1" class="mx-1 select-none text-sm font-medium text-muted-foreground"
                                    >-</PinInputSeparator
                                >
                            </template>
                        </PinInputGroup>
                    </PinInput>
                    <div class="flex items-center gap-2 text-xs">
                        <input id="trust" type="checkbox" v-model="form.trust" class="rounded border-muted bg-transparent" :disabled="isExpired()" />
                        <label for="trust" class="cursor-pointer">{{ t('security.2fa.trust_this_device') }}</label>
                    </div>
                    <div class="flex w-full flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="resend"
                                class="text-xs underline hover:text-primary disabled:opacity-40"
                                :disabled="submitting || !resendWindow.resendAllowed.value || resendWindow.resendSuspended.value"
                            >
                                {{ t('security.2fa.resend') }}
                            </button>
                            <span v-if="resendWindow.resendSuspended.value" class="text-[10px] font-medium text-destructive">
                                {{ t('security.2fa.resend_suspended') || 'Suspended' }}
                            </span>
                            <span
                                v-else-if="!resendWindow.resendAllowed.value && (resendWindow.resendWait.value || 0) > 0"
                                class="text-[10px] text-muted-foreground"
                                :title="t('security.2fa.resend_waiting')"
                            >
                                {{ resendWindow.formatCompact(resendWindow.resendWait.value || 0) }}
                            </span>
                        </div>
                        <div v-if="attempts.showAttemptLabel.value" class="text-[10px] text-muted-foreground">{{ attempts.attemptLabel.value }}</div>
                        <div v-if="attempts.showWarning.value" class="text-[10px] font-medium text-amber-600">{{ attempts.warningLabel.value }}</div>
                        <button type="button" @click="cancel" class="text-xs text-muted-foreground underline hover:text-foreground">
                            {{ t('security.2fa.cancel') }}
                        </button>
                    </div>
                    <div v-if="error" class="text-xs font-medium text-destructive">{{ error }}</div>
                </div>
            </form>
            <p class="text-center text-[10px] text-muted-foreground">{{ t('security.2fa.disclaimer') }}</p>
        </div>
    </div>
</template>

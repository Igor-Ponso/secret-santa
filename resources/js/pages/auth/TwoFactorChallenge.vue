<script setup lang="ts">
import { PinInput, PinInputGroup, PinInputSeparator, PinInputSlot } from '@/components/ui/pin-input';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface PendingAction {
    type?: string;
    id?: string | number | null;
    name?: string | null;
}
interface Props {
    mode?: string;
    resent?: boolean;
    expires_at?: string;
    remaining_seconds?: number;
    server_time?: string;
    resend_allowed?: boolean;
    resend_wait_seconds?: number;
    resend_suspended?: boolean;
    resend_min_interval?: number;
    next_resend_at?: string | null;
    resend_attempt_count?: number;
    resend_max_before_suspend?: number;
    pending_action?: PendingAction | null;
}
const props = defineProps<Props>();
const { t } = useI18n();

function pendingLabel() {
    const type = props.pending_action?.type;
    if (!type) return '';
    const map: Record<string, string> = {
        enable_2fa: 'Enable Two-Factor Authentication',
        disable_2fa: 'Disable Two-Factor Authentication',
        revoke_all: 'Revoke all trusted devices',
        revoke_one: 'Revoke a trusted device',
        rename: 'Rename a trusted device',
    };
    return map[type] || '';
}
const form = useForm({ code: '', trust: true });
const length = 6;
const values = ref<string[]>(Array(length).fill(''));
const submitting = ref(false);
const error = ref<string | null>(null);
const resentFlag = ref(props.resent);
const remaining = ref(props.remaining_seconds ?? 0);
// Resend / throttle state & attempt labels
const resendWait = ref(props.resend_wait_seconds ?? 0);
const nextResendAt = ref<Date | null>(props.next_resend_at ? new Date(props.next_resend_at) : null);
const resendAllowed = ref(props.resend_allowed ?? true);
const resendSuspended = ref(props.resend_suspended ?? false);
let resendTimer: any = null;
let timer: any = null;
const attemptLabel = computed(() => {
    if (typeof props.resend_attempt_count !== 'number' || typeof props.resend_max_before_suspend !== 'number') return '';
    const current = (props.resend_attempt_count || 0) + 1;
    const total = props.resend_max_before_suspend;
    return `Attempt ${current} of ${total}`;
});
const showAttemptLabel = computed(() => attemptLabel.value && !resendSuspended.value);
const warningLabel = computed(() => {
    if (resendSuspended.value || typeof props.resend_attempt_count !== 'number' || typeof props.resend_max_before_suspend !== 'number') return '';
    const remaining = props.resend_max_before_suspend - props.resend_attempt_count;
    if (remaining > 2 || props.resend_attempt_count >= props.resend_max_before_suspend) return '';
    return `After ${props.resend_max_before_suspend} attempts your account will be temporarily locked.`;
});
const showWarning = computed(() => warningLabel.value.length > 0);

function formatResendWait(seconds: number): string {
    if (seconds <= 0) return '0s';
    if (seconds < 60) return `${seconds}s`;
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function startTimer() {
    if (timer) clearInterval(timer);
    if (remaining.value <= 0) return;
    timer = setInterval(() => {
        if (remaining.value > 0) remaining.value -= 1;
        if (remaining.value <= 0) {
            clearInterval(timer);
            timer = null;
        }
    }, 1000);
}
startTimer();
onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
    if (resendTimer) clearInterval(resendTimer);
});

const isExpired = () => remaining.value <= 0;
const mmss = () => {
    const m = Math.floor(remaining.value / 60)
        .toString()
        .padStart(2, '0');
    const s = Math.floor(remaining.value % 60)
        .toString()
        .padStart(2, '0');
    return `${m}:${s}`;
};

watch(values, (v) => {
    if (isExpired()) return;
    form.code = v.join('').toUpperCase();
    error.value = null;
    if (form.code.length === length) {
        submit();
    }
});

function submit() {
    if (submitting.value) return;
    submitting.value = true;
    form.post(route('2fa.verify'), {
        preserveScroll: true,
        onError: (errs) => {
            error.value = (errs.code as string) || 'Invalid code';
            submitting.value = false;
            // reset code fields
            values.value = Array(length).fill('');
        },
        onSuccess: () => {
            submitting.value = false;
        },
    });
}

function resend() {
    if (resendSuspended.value) return;
    if (!resendAllowed.value) return;
    router.post(
        route('2fa.resend'),
        {},
        {
            onSuccess: () => {
                router.reload({ only: ['expires_at', 'remaining_seconds', 'resent', 'resend_allowed', 'resend_wait_seconds', 'resend_suspended'] });
            },
            onError: (errs) => {
                // Expect error bag key 'resend'
                const msg = (errs.resend as string) || 'Cannot resend now.';
                error.value = msg;
                // Try to extract wait seconds: "Please wait Xs before..."
                const match = msg.match(/wait\s+(\d+)s/i);
                if (match) {
                    const w = parseInt(match[1], 10);
                    if (!isNaN(w) && w > 0) {
                        resendWait.value = w;
                        resendAllowed.value = false;
                        if (resendTimer) clearInterval(resendTimer);
                        resendTimer = setInterval(() => {
                            if (resendWait.value > 0) resendWait.value -= 1;
                            if (resendWait.value <= 0) {
                                resendAllowed.value = !resendSuspended.value;
                                clearInterval(resendTimer);
                                resendTimer = null;
                            }
                        }, 1000);
                    }
                }
                // Try partial reload to update wait seconds
                router.reload({ only: ['resend_allowed', 'resend_wait_seconds', 'resend_suspended'] });
            },
        },
    );
}

function cancel() {
    router.post(route('2fa.cancel'));
}

// React to updated props after resend (Inertia partial reload)
watch(
    () => props.remaining_seconds,
    (val) => {
        if (typeof val === 'number' && val > 0) {
            remaining.value = val;
            // reset state for fresh attempt
            values.value = Array(length).fill('');
            error.value = null;
            resentFlag.value = props.resent;
            startTimer();
        }
    },
);

// Watch resend props updates
watch(
    () => [props.resend_wait_seconds, props.next_resend_at, props.resend_allowed, props.resend_suspended],
    () => {
        resendSuspended.value = !!props.resend_suspended;
        nextResendAt.value = props.next_resend_at ? new Date(props.next_resend_at) : null;
        if (nextResendAt.value) {
            const diff = Math.ceil((nextResendAt.value.getTime() - Date.now()) / 1000);
            resendWait.value = diff > 0 ? diff : 0;
        } else {
            resendWait.value = props.resend_wait_seconds ?? 0;
        }
        resendAllowed.value = !!props.resend_allowed && resendWait.value === 0 && !resendSuspended.value;
        if (resendTimer) clearInterval(resendTimer);
        if (!resendSuspended.value && resendWait.value > 0) {
            resendTimer = setInterval(() => {
                if (nextResendAt.value) {
                    const diff2 = Math.ceil((nextResendAt.value.getTime() - Date.now()) / 1000);
                    resendWait.value = diff2 > 0 ? diff2 : 0;
                } else if (resendWait.value > 0) {
                    resendWait.value -= 1;
                }
                if (resendWait.value <= 0) {
                    resendAllowed.value = !resendSuspended.value;
                    clearInterval(resendTimer);
                    resendTimer = null;
                }
            }, 1000);
        }
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
                    {{ t('security.2fa.pending_confirm') }} {{ pendingLabel() }}
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
                                :disabled="submitting || !resendAllowed || resendSuspended"
                            >
                                {{ t('security.2fa.resend') }}
                            </button>
                            <span v-if="resendSuspended" class="text-[10px] font-medium text-destructive">{{
                                t('security.2fa.resend_suspended') || 'Suspended'
                            }}</span>
                            <span
                                v-else-if="!resendAllowed && resendWait > 0"
                                class="text-[10px] text-muted-foreground"
                                :title="t('security.2fa.resend_waiting')"
                            >
                                {{ formatResendWait(resendWait) }}
                            </span>
                        </div>
                        <div v-if="showAttemptLabel" class="text-[10px] text-muted-foreground">{{ attemptLabel }}</div>
                        <div v-if="showWarning" class="text-[10px] font-medium text-amber-600">{{ warningLabel }}</div>
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

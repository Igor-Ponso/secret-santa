<script setup lang="ts">
import { PinInput, PinInputGroup, PinInputSlot } from '@/components/ui/pin-input';
import { Head, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ mode: string; resent: boolean; expires_at?: string | null; remaining_seconds?: number; server_time?: string }>();
const form = useForm({ code: '', trust: true });
const { t } = useI18n();
const length = 6;
const values = ref<string[]>(Array(length).fill(''));
const submitting = ref(false);
const error = ref<string | null>(null);
const resentFlag = ref(props.resent);
const remaining = ref(props.remaining_seconds ?? 0);
let timer: any = null;

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
    router.post(route('2fa.resend'), {}, { onSuccess: () => router.reload({ only: ['expires_at', 'remaining_seconds', 'resent'] }) });
}

function cancel() {
    router.post(route('2fa.cancel'));
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <Head title="Two-Factor" />
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-xl font-semibold tracking-tight">Security Check</h1>
                <p class="text-sm text-muted-foreground" v-if="!isExpired()">{{ t('security.2fa.instructions') }}</p>
                <p class="text-sm text-destructive" v-else>{{ t('security.2fa.expired') }}</p>
                <p v-if="resentFlag" class="text-xs text-emerald-600">{{ t('security.2fa.new_code_sent') }}</p>
                <p v-if="!isExpired()" class="text-xs">{{ t('security.2fa.expires_in', { time: mmss() }) }}</p>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="flex flex-col items-center gap-4">
                    <PinInput v-model="values" :otp="true" :disabled="submitting || isExpired()" class="flex gap-2">
                        <PinInputGroup>
                            <PinInputSlot v-for="(val, idx) in values" :key="idx" :index="idx" class="h-12 w-10 text-lg font-semibold" />
                        </PinInputGroup>
                    </PinInput>
                    <div class="flex items-center gap-2 text-xs">
                        <input id="trust" type="checkbox" v-model="form.trust" class="rounded border-muted bg-transparent" :disabled="isExpired()" />
                        <label for="trust" class="cursor-pointer">{{ t('security.2fa.trust_this_device') }}</label>
                    </div>
                    <div class="flex w-full flex-col gap-2">
                        <button type="button" @click="resend" class="text-xs underline hover:text-primary disabled:opacity-40" :disabled="submitting">
                            {{ t('security.2fa.resend') }}
                        </button>
                        <button type="button" @click="cancel" class="text-xs text-muted-foreground underline hover:text-foreground">
                            {{ t('security.2fa.cancel') }}
                        </button>
                    </div>
                    <div v-if="error" class="text-xs font-medium text-destructive">{{ error }}</div>
                </div>
            </form>
            <p class="text-center text-[10px] text-muted-foreground">For your security this step appears on new devices. Keep your email secure.</p>
        </div>
    </div>
</template>

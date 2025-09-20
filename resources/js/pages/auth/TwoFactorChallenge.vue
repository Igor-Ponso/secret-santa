<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
// Corrigido: usar componentes locais shadcn-vue gerados em '@/components/ui/pin-input'
import { PinInput, PinInputGroup, PinInputSlot } from '@/components/ui/pin-input';
import { ref, watch } from 'vue';

const props = defineProps<{ mode: string; resent: boolean }>();
const form = useForm({ code: '', trust: true });
const length = 6; // align with config default
const values = ref<string[]>(Array(length).fill(''));
const submitting = ref(false);
const error = ref<string | null>(null);
const resentFlag = ref(props.resent);

watch(values, (v) => {
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
    router.post(
        route('2fa.resend'),
        {},
        {
            onSuccess: () => {
                resentFlag.value = true;
                setTimeout(() => (resentFlag.value = false), 5000);
            },
        },
    );
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <Head title="Two-Factor" />
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-xl font-semibold tracking-tight">Security Check</h1>
                <p class="text-sm text-muted-foreground">Enter the verification code we sent to your email.</p>
                <p v-if="resentFlag" class="text-xs text-emerald-600">New code sent.</p>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="flex flex-col items-center gap-4">
                    <PinInput v-model="values" :otp="true" :disabled="submitting" class="flex gap-2">
                        <PinInputGroup>
                            <PinInputSlot v-for="(val, idx) in values" :key="idx" :index="idx" class="h-12 w-10 text-lg font-semibold" />
                        </PinInputGroup>
                    </PinInput>
                    <div class="flex items-center gap-2 text-xs">
                        <input id="trust" type="checkbox" v-model="form.trust" class="rounded border-muted bg-transparent" />
                        <label for="trust" class="cursor-pointer">Trust this device</label>
                    </div>
                    <button type="button" @click="resend" class="text-xs underline hover:text-primary" :disabled="submitting">Resend code</button>
                    <div v-if="error" class="text-xs font-medium text-destructive">{{ error }}</div>
                </div>
            </form>
            <p class="text-center text-[10px] text-muted-foreground">For your security this step appears on new devices. Keep your email secure.</p>
        </div>
    </div>
</template>

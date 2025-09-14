<script setup lang="ts">
import SocialLoginButtons from '@/components/auth/SocialLoginButtons.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const { t } = useI18n();
</script>

<template>
    <AuthBase
        :title="t('auth.login_title', 'Log in to your account')"
        :description="t('auth.login_desc', 'Enter your email and password below to log in')"
    >
        <Head :title="t('auth.login', 'Log in')" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email', 'Email address') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('auth.password', 'Password') }}</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            {{ t('auth.forgot_password', 'Forgot password?') }}
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        :placeholder="t('auth.password_placeholder', 'Password')"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div
                    class="group relative rounded-md border border-neutral-200/70 bg-neutral-50/70 px-4 py-3 shadow-sm ring-0 transition focus-within:border-red-500 focus-within:bg-white focus-within:shadow-md hover:bg-white hover:shadow dark:border-neutral-700/70 dark:bg-neutral-800/60 dark:focus-within:border-red-400 dark:hover:bg-neutral-800/80"
                    :tabindex="3"
                >
                    <Label
                        for="remember"
                        class="flex cursor-pointer select-none items-center gap-3 text-sm font-medium text-neutral-700 dark:text-neutral-200"
                    >
                        <Checkbox
                            id="remember"
                            v-model:checked="form.remember"
                            :tabindex="4"
                            class="h-5 w-5 rounded-sm border-neutral-400 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white data-[state=checked]:border-red-600 data-[state=checked]:bg-red-600 dark:border-neutral-500 dark:focus-visible:ring-offset-neutral-900 dark:data-[state=checked]:border-red-500 dark:data-[state=checked]:bg-red-500"
                        />
                        <span class="leading-tight">
                            {{ t('auth.remember', 'Remember me') }}
                            <span class="block text-[11px] font-normal text-neutral-500 dark:text-neutral-400">
                                {{ t('auth.remember_hint', 'Keep me signed in on this device') }}
                            </span>
                        </span>
                    </Label>
                    <span
                        class="pointer-events-none absolute inset-0 rounded-md ring-2 ring-transparent transition group-focus-within:ring-red-500/60 group-hover:ring-red-500/30"
                        aria-hidden="true"
                    />
                </div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.login', 'Log in') }}
                </Button>
            </div>

            <SocialLoginButtons />

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.no_account', "Don't have an account?") }}
                <TextLink :href="route('register')" :tabindex="5">{{ t('auth.sign_up', 'Sign up') }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

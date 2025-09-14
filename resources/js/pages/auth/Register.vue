<script setup lang="ts">
import SocialLoginButtons from '@/components/auth/SocialLoginButtons.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase
        :title="t('auth.register_title', 'Create your account')"
        :description="t('auth.register_desc', 'Fill the form below to create your account')"
    >
        <Head :title="t('auth.register', 'Register')" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">{{ t('auth.name', 'Full name') }}</Label>
                    <Input id="name" type="text" required autofocus autocomplete="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email', 'Email address') }}</Label>
                    <Input id="email" type="email" required autocomplete="email" v-model="form.email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('auth.password', 'Password') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        v-model="form.password"
                        :placeholder="t('auth.password_placeholder', 'Password')"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ t('auth.password_confirm', 'Confirm password') }}</Label>
                    <Input id="password_confirmation" type="password" required autocomplete="new-password" v-model="form.password_confirmation" />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.create_account', 'Create account') }}
                </Button>
            </div>

            <SocialLoginButtons />

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.has_account', 'Already have an account?') }}
                <TextLink :href="route('login')">{{ t('auth.sign_in', 'Log in') }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

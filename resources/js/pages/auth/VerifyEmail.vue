<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

defineProps<{
    status?: string;
}>();

const form = useForm({});
const { t } = useI18n();

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout
        :title="t('auth.verify_email', 'Verify email')"
        :description="t('auth.verify_desc', 'Please verify your email address by clicking on the link we just emailed to you.')"
    >
        <Head :title="t('auth.verify_email', 'Verify email')" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ t('auth.verification_sent') }}
        </div>

        <form @submit.prevent="submit" class="space-y-6 text-center">
            <Button :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ t('auth.resend_verification') }}
            </Button>
            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm"> {{ t('auth.verify_logout') }} </TextLink>
        </form>
    </AuthLayout>
</template>

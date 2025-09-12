<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import LanguageTabs from '@/components/LanguageTabs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
// import { type BreadcrumbItem } from '@/types'; // no longer directly used
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps<{ available_locales: string[]; current_locale: string }>();
const { t } = useI18n();

const breadcrumbItems = computed(() => [
    { title: t('settings.breadcrumb_settings'), href: '/settings' },
    { title: t('settings.language'), href: '/settings/language' },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.language') || 'Language settings'" />
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('settings.language') || 'Language settings'"
                    :description="t('settings.language_desc') || 'Select the interface language'"
                />
                <LanguageTabs :locales="available_locales" :current="current_locale" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

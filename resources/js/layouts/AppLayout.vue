<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { errorToast, infoToast, successToast } from '@/lib/notifications';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
let lastSuccess: string | null = null;
let lastError: string | null = null;
let lastInfo: string | null = null;

watch(
    () => page.props.flash as any,
    (flash) => {
        if (!flash) return;
        if (flash.success && flash.success !== lastSuccess) {
            lastSuccess = String(flash.success);
            successToast(lastSuccess);
        }
        if (flash.info && flash.info !== lastInfo) {
            lastInfo = String(flash.info);
            infoToast(lastInfo);
        }
        if (flash.error && flash.error !== lastError) {
            lastError = String(flash.error);
            errorToast(lastError);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>

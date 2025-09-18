<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { errorToast, infoToast, successToast } from '@/lib/notifications';
import type { BreadcrumbItemType } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
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
let lastWarning: string | null = null;
let clearedOnce = false;

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
        if (flash.warning && flash.warning !== lastWarning) {
            lastWarning = String(flash.warning);
            infoToast(lastWarning); // reuse info styling for now
        }
        if (flash.error && flash.error !== lastError) {
            lastError = String(flash.error);
            errorToast(lastError);
        }
        // After displaying, request a silent visit to clear flash to avoid re-trigger on browser back
        if (!clearedOnce && (flash.success || flash.info || flash.error || flash.warning)) {
            // Trigger a minimal reload to clear server flash (Inertia remembers props otherwise on back)
            router.get(
                window.location.pathname + window.location.search,
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    onSuccess: () => {
                        (page.props as any).flash = {};
                        clearedOnce = true;
                    },
                },
            );
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>

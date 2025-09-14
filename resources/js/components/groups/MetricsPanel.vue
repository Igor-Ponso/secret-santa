<script setup lang="ts">
import type { GroupMetrics } from '@/interfaces/group';
import { useI18n } from 'vue-i18n';

defineProps<{ metrics: GroupMetrics | null; isOwner: boolean }>();
const { t } = useI18n();
</script>

<template>
    <div v-if="isOwner && metrics" class="rounded border p-4 text-sm">
        <h2 class="mb-3 text-base font-semibold">{{ t('groups.overview') || 'Visão Geral' }}</h2>
        <div class="grid gap-3 sm:grid-cols-5">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium">{{ t('groups.metrics_pending') }}</span>
                <span class="text-lg font-bold">{{ metrics.pending }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium">{{ t('groups.metrics_accepted') }}</span>
                <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ metrics.accepted }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium">{{ t('groups.metrics_declined') }}</span>
                <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ metrics.declined }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium">{{ t('groups.metrics_invited') }}</span>
                <span class="text-lg font-bold">{{ metrics.invited }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium">{{ t('groups.participants') }}</span>
                <span class="text-lg font-bold">{{ metrics.participants }}</span>
            </div>
        </div>
    </div>
</template>

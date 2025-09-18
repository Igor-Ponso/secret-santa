<script setup lang="ts">
import { useI18n } from 'vue-i18n';

interface Props {
    activities: any[];
}

defineProps<Props>();
const { t } = useI18n();
</script>

<template>
    <div v-if="activities && activities.length" class="space-y-2 rounded border p-4">
        <h2 class="text-sm font-semibold">{{ t('groups.activities') }}</h2>
        <ul class="space-y-1 text-xs">
            <li v-for="a in activities" :key="a.id" class="flex items-center justify-between">
                <span class="font-mono">{{ a.action }}</span>
                <span class="text-muted-foreground">{{ new Date(a.created_at).toLocaleString() }}</span>
            </li>
        </ul>
    </div>
</template>

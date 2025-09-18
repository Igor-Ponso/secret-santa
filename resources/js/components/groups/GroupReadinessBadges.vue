<script setup lang="ts">
import type { GroupMetrics } from '@/interfaces/group';
import { computed } from 'vue';

interface Props {
    metrics: GroupMetrics | null;
    compact?: boolean;
}
const props = defineProps<Props>();

const threshold = computed(() => (props.metrics?.readiness_threshold !== undefined ? props.metrics!.readiness_threshold! : 50));

const badges = computed(() => {
    if (!props.metrics) return [] as { label: string; kind: 'ok' | 'warn' | 'neutral'; hint?: string }[];
    const arr: { label: string; kind: 'ok' | 'warn' | 'neutral'; hint?: string }[] = [];
    if (props.metrics.min_participants_met !== undefined) {
        arr.push({
            label: props.metrics.min_participants_met ? 'Participantes mínimos ✅' : 'Mínimo de participantes insuficiente',
            kind: props.metrics.min_participants_met ? 'ok' : 'warn',
            hint: 'É necessário pelo menos 2 participantes para o sorteio.',
        });
    }
    if (props.metrics.wishlist_coverage_percent !== undefined) {
        const pct = props.metrics.wishlist_coverage_percent || 0;
        arr.push({
            label: `Wishlists: ${pct}%`,
            kind: pct >= threshold.value ? 'ok' : 'warn',
            hint: `Percentual de participantes com ao menos 1 item. Limite para prontidão: ${threshold.value}%.`,
        });
    }
    if (props.metrics.ready_for_draw !== undefined) {
        arr.push({
            label: props.metrics.ready_for_draw ? 'Pronto para sorteio' : 'Ainda não pronto',
            kind: props.metrics.ready_for_draw ? 'ok' : 'neutral',
            hint: `Heurística: participantes mínimos + >=${threshold.value}% com wishlist.`,
        });
    }
    return arr;
});
</script>

<template>
    <div v-if="badges.length" :class="['flex flex-wrap', compact ? 'gap-1' : 'gap-2']">
        <span
            v-for="b in badges"
            :key="b.label"
            class="inline-flex items-center gap-1 rounded-full border text-[11px] font-medium"
            :class="
                [
                  compact ? 'px-2 py-0.5' : 'px-3 py-1',
                  b.kind === 'ok'
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-200'
                    : b.kind === 'warn'
                      ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-200'
                      : 'border-neutral-300 bg-neutral-50 text-neutral-700 dark:border-neutral-600 dark:bg-neutral-800/50 dark:text-neutral-200',
            ]"
            :title="b.hint"
        >
            {{ b.label }}
        </span>
        <div
            v-if="(props.metrics?.wishlist_coverage_percent ?? 0) > 0"
            :class="['w-full', compact ? 'max-w-xs' : 'max-w-sm']"
        >
            <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-muted-foreground">
                <span>Cobertura de wishlist</span>
                <span>{{ props.metrics!.wishlist_coverage_percent }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded bg-neutral-200 dark:bg-neutral-700">
                <div
                    class="h-full transition-all"
                    :class="(props.metrics!.wishlist_coverage_percent || 0) >= threshold ? 'bg-primary' : 'bg-primary/40'"
                    :style="{ width: Math.min(100, props.metrics!.wishlist_coverage_percent || 0) + '%' }"
                />
            </div>
        </div>
    </div>
</template>

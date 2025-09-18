<script setup lang="ts">
import type { GroupMetrics } from '@/interfaces/group';
import { computed } from 'vue';

interface Props {
    metrics: GroupMetrics | null;
    compact?: boolean;
}
const props = defineProps<Props>();

const threshold = computed(() => (props.metrics?.readiness_threshold !== undefined ? props.metrics!.readiness_threshold! : 50));

// Revised badge strategy:
// - Always show wishlist coverage badge (neutral/informational if below threshold; success if above)
// - Show participant minimum badge only when unmet
// - When ready, show a success badge and keep wishlist badge (so user still sees %)
interface Badge {
    label: string;
    kind: 'ok' | 'info' | 'warn';
    hint?: string;
}
const badges = computed<Badge[]>(() => {
    const m = props.metrics;
    if (!m) return [];
    const list: Badge[] = [];
    const minOk = m.min_participants_met === true;
    const cov = m.wishlist_coverage_percent ?? 0;
    const covOk = cov >= threshold.value;
    const ready = m.ready_for_draw === true;

    if (!minOk) {
        list.push({ label: 'Mínimo de 2 participantes é obrigatório', kind: 'warn', hint: 'Adicione mais participantes para poder sortear.' });
    }

    // Wishlist informational badge (optional nature conveyed in hint)
    list.push({
        label: `Wishlist: ${cov}%`,
        kind: covOk ? 'ok' : 'info',
        hint: 'Wishlist não é obrigatória para o sorteio, mas ajuda seu amigo secreto a escolher melhor.',
    });

    if (ready) {
        list.unshift({ label: 'Pronto para sorteio', kind: 'ok', hint: `Requisitos atendidos (>=2 participantes). Wishlist é opcional.` });
    }
    return list;
});
</script>

<template>
    <div v-if="badges.length" :class="['flex flex-wrap', compact ? 'gap-1' : 'gap-2']">
        <span
            v-for="b in badges"
            :key="b.label"
            class="inline-flex items-center gap-1 rounded-full border text-[11px] font-medium"
            :class="[
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
        <div v-if="props.metrics && (props.metrics.wishlist_coverage_percent || 0) > 0" :class="['w-full', compact ? 'max-w-xs' : 'max-w-sm']">
            <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-muted-foreground">
                <span>Cobertura de wishlist</span>
                <span>{{ props.metrics!.wishlist_coverage_percent || 0 }}%</span>
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

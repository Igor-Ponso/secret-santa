<script setup lang="ts">
import GroupReadinessBadges from '@/components/groups/GroupReadinessBadges.vue';
import RecipientPanel from '@/components/groups/RecipientPanel.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    group: any; // expects fields: is_owner, has_draw, can_draw, participant_count, days_until_draw, draw_date, metrics
    drawing: boolean;
    recipient: any;
    recipientWishlist: any[];
    loadingRecipient: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'run-draw'): void }>();
const { t } = useI18n();

// Derived state helpers
const readiness = computed(() => props.group.metrics || null);
const coveragePct = computed(() => readiness.value?.wishlist_coverage_percent ?? 0);
const readinessThreshold = computed(() => readiness.value?.readiness_threshold ?? 50);

const canManualDraw = computed(() => props.group.is_owner && !props.group.has_draw);

// Show readiness badges? (owner, metrics available, before draw)
const showBadges = computed(() => props.group.is_owner && readiness.value && !props.group.has_draw);

const drawCountdownLabel = computed(() => {
    if (!props.group.draw_date || props.group.has_draw) return '';
    const d = props.group.days_until_draw;
    const fallbackPlural = `${d} dia${d === 1 ? '' : 's'}`;
    if (d > 0) {
        const label = t('groups.draw_in_days', {
            count: d,
            days: t('common.days', { count: d }) || fallbackPlural,
            date: props.group.draw_date,
        });
        // Avoid leaking raw key if translation missing
        return label === 'groups.draw_in_days' ? fallbackPlural : label;
    }
    if (d === 0) {
        const today = t('groups.draw_today');
        return today === 'groups.draw_today' ? t('groups.draw_today') : today;
    }
    const past = t('groups.draw_date_passed');
    return past === 'groups.draw_date_passed' ? 'Data planejada já passou.' : past;
});

const actionLabel = computed(() => {
    if (props.drawing) return t('groups.drawing') || 'Sorteando...';
    if (props.group.can_draw) return t('groups.run_draw');
    return t('groups.waiting_participants');
});

// Mini coverage bar removed to avoid duplication with badges component

const insufficientParticipants = computed(() => !props.group.has_draw && readiness.value && readiness.value.min_participants_met === false);
const insufficientWishlist = computed(
    () => !props.group.has_draw && readiness.value && readiness.value.min_participants_met && coveragePct.value < readinessThreshold.value,
);
</script>

<template>
    <div class="space-y-4 rounded border p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-1">
                <h2 class="text-sm font-semibold">{{ t('groups.draw') }}</h2>
                <div v-if="drawCountdownLabel" class="text-xs" :class="{ 'text-yellow-700': props.group.days_until_draw < 0 }">
                    <strong>{{ drawCountdownLabel }}</strong>
                </div>
                <GroupReadinessBadges v-if="showBadges" :metrics="readiness" compact class="mt-1" />
                <div v-else-if="props.group.has_draw" class="mt-1 text-xs font-medium text-green-600 dark:text-green-400">
                    {{ t('groups.draw_complete') }}
                </div>
            </div>
            <div class="ml-auto flex min-w-[160px] flex-col items-end gap-2">
                <div v-if="canManualDraw" class="w-full">
                    <button
                        @click="emit('run-draw')"
                        :disabled="drawing || !props.group.can_draw"
                        class="flex w-full items-center justify-center gap-1 rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        <LoaderCircle v-if="drawing" class="h-4 w-4 animate-spin" />
                        {{ actionLabel }}
                    </button>
                </div>
                <div v-else-if="props.group.has_draw" class="text-xs text-muted-foreground">{{ t('groups.draw_complete') }}</div>
            </div>
        </div>
        <div v-if="!showBadges" class="flex flex-wrap gap-3 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1 rounded bg-accent/50 px-2 py-0.5">
                {{ t('common.misc.participants_count', { count: props.group.participant_count }) }}
            </span>
            <span v-if="props.group.has_draw" class="text-green-600 dark:text-green-400">
                {{ t('groups.draw_complete') }}
            </span>
            <span v-else-if="insufficientParticipants" class="text-amber-600 dark:text-amber-400">
                {{ t('groups.min_participants_hint') }}
            </span>
            <span v-else-if="insufficientWishlist" class="text-amber-600 dark:text-amber-400">
                {{
                    t('groups.wishlist_coverage_shortfall', { coverage: coveragePct, threshold: readinessThreshold }) ||
                    `Wishlists: ${coveragePct}% / ${readinessThreshold}%`
                }}
            </span>
        </div>
        <RecipientPanel :recipient="recipient" :wishlist="recipientWishlist" :loading="loadingRecipient" :has-draw="props.group.has_draw" />
    </div>
</template>

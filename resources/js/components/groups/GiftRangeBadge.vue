<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    minCents: number | null;
    maxCents: number | null;
    currency?: string; // ISO 4217 e.g. 'BRL', 'USD', 'EUR'
    locale?: string; // BCP47 e.g. 'pt-BR', 'en-US'
    dense?: boolean; // smaller padding variant
}

const props = withDefaults(defineProps<Props>(), {
    currency: 'BRL',
    locale: 'pt-BR',
    dense: false,
});

const { t } = useI18n();

const formatted = computed(() => {
    const fmt = (v: number) => (v / 100).toLocaleString(props.locale, { style: 'currency', currency: props.currency });
    if (props.minCents !== null && props.maxCents !== null) return `${fmt(props.minCents)} – ${fmt(props.maxCents)}`;
    if (props.minCents !== null) return `≥ ${fmt(props.minCents)}`;
    if (props.maxCents !== null) return `≤ ${fmt(props.maxCents)}`;
    return '';
});
</script>

<template>
    <div
        v-if="minCents !== null || maxCents !== null"
        :class="[
            'inline-flex items-center gap-1 rounded-full border bg-gradient-to-r from-emerald-500/10 via-primary/10 to-fuchsia-500/10 text-[11px] font-medium tracking-wide shadow-sm backdrop-blur',
            dense ? 'px-2 py-0.5' : 'px-3 py-1',
        ]"
    >
        <span class="text-muted-foreground">{{ t('groups.gift_range') || 'Faixa' }}:</span>
        <span class="font-semibold">{{ formatted }}</span>
    </div>
</template>

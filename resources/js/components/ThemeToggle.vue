<script setup lang="ts">
import { useAppearance, type Appearance } from '@/composables/useAppearance';
import { THEME_MODES } from '@/types/theme';
import { ref } from 'vue';

// Explicit descriptive naming
const { appearance: currentAppearance, updateAppearance } = useAppearance();

const groupRef = ref<HTMLElement | null>(null);

function setAppearance(value: Appearance) {
    if (currentAppearance.value === value) return;
    updateAppearance(value);
}

function onKey(e: KeyboardEvent) {
    const idx = THEME_MODES.findIndex((m) => m.value === currentAppearance.value);
    if (idx === -1) return;
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        const next = THEME_MODES[(idx + 1) % THEME_MODES.length];
        setAppearance(next.value);
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = THEME_MODES[(idx - 1 + THEME_MODES.length) % THEME_MODES.length];
        setAppearance(prev.value);
    }
}
</script>

<template>
    <div
        ref="groupRef"
        role="radiogroup"
        aria-label="Theme appearance"
        class="inline-flex overflow-hidden rounded-md border border-border bg-background shadow-sm"
        @keydown="onKey"
    >
        <button
            v-for="mode in THEME_MODES"
            :key="mode.value"
            type="button"
            role="radio"
            :aria-checked="currentAppearance === mode.value"
            :tabindex="currentAppearance === mode.value ? 0 : -1"
            @click="setAppearance(mode.value)"
            :aria-label="mode.label"
            :title="mode.label"
            class="group flex items-center gap-1 px-2 py-1.5 text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-ring/60"
            :class="[
                currentAppearance === mode.value ? 'bg-primary text-primary-foreground' : 'text-foreground/70 hover:bg-muted hover:text-foreground',
            ]"
        >
            <component :is="mode.icon" class="h-3.5 w-3.5" />
            <span
                class="ml-1 max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-200 focus:max-w-[64px] focus:opacity-100 group-hover:max-w-[64px] group-hover:opacity-100"
                >{{ mode.label }}</span
            >
        </button>
    </div>
</template>

<style scoped>
button {
    position: relative;
}
button + button {
    border-left: 1px solid hsl(var(--border));
}
</style>

<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { getActiveProviders, type SocialProviderDefinition } from '@/types/auth/providers';

const providers = getActiveProviders();
const isNavigating = ref<string | null>(null);
const failedIcons = ref<Set<string>>(new Set());

function redirectToProvider(provider: SocialProviderDefinition['id']) {
    if (isNavigating.value) return; // prevent double-clicks
    isNavigating.value = provider;
    window.location.href = route('social.login', provider);
}

function handleIconError(id: string) {
    failedIcons.value.add(id);
}
</script>

<template>
    <div class="flex w-full items-center justify-center gap-4">
        <Button
            v-for="p in providers"
            :key="p.id"
            :aria-label="`Continue with ${p.label}`"
            :disabled="isNavigating && isNavigating !== p.id"
            @click="redirectToProvider(p.id)"
            variant="ghost"
            size="icon"
            class="social-btn relative overflow-hidden border border-muted/60 bg-white dark:bg-zinc-900 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 focus-visible:ring-2 focus-visible:ring-ring/60 disabled:opacity-50"
        >
            <template v-if="!failedIcons.has(p.id)">
                <!-- Inline SVG wrapper ensures consistent sizing -->
                <span v-if="p.svg" class="inline-svg h-5 w-5" v-html="p.svg" aria-hidden="true" />
                <img
                    v-else
                    :src="p.icon"
                    :alt="`${p.label} logo`"
                    class="h-5 w-5"
                    loading="lazy"
                    @error="handleIconError(p.id)"
                />
            </template>
            <template v-else>
                <span class="text-xs font-semibold">{{ p.label[0] }}</span>
            </template>
            <span
                v-if="isNavigating === p.id"
                class="absolute inset-0 flex items-center justify-center text-[10px] font-medium bg-white/60 dark:bg-black/40"
            >
                …
            </span>
        </Button>
    </div>
</template>

<style scoped>
/* Normalize inline SVG */
.inline-svg :deep(svg) {
    width: 1.25rem; /* 20px matches h-5 */
    height: 1.25rem;
    display: block;
}
.inline-svg { display: inline-flex; align-items: center; justify-content: center; }

/* Ensure monochrome logos (e.g., GitHub black) remain visible on dark backgrounds */
.social-btn :deep(svg) { fill: currentColor; }
.social-btn :deep(path[fill="#000" i]) { fill: currentColor; }
.social-btn { color: #111827; /* slate-900 */ }
.dark .social-btn { color: #f1f5f9; /* slate-100 */ }
.social-btn:hover { color: #111827; }
.dark .social-btn:hover { color: #f1f5f9; }

/* In case an SVG has embedded background conflicting with dark mode, add subtle ring */
.dark .social-btn:focus-visible { outline: none; }
</style>

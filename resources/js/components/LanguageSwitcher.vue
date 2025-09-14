<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale, availableLocales } = useI18n();
const current = ref(locale.value);

function setCookieLocale(l: string) {
    document.cookie = `locale=${l};path=/;max-age=${60 * 60 * 24 * 365}`;
}

function switchTo(l: string) {
    current.value = l;
    locale.value = l;
    setCookieLocale(l);
    try {
        localStorage.setItem('app_locale', l);
    } catch {}
}

watch(
    () => locale.value,
    (v) => {
        current.value = v;
    },
);

const labels: Record<string, string> = {
    en: 'English',
    pt_BR: 'Português',
    fr: 'Français',
};

// Short code display on trigger (EN, PT, FR)
function shortLabel(l: string) {
    if (l === 'pt_BR') return 'PT';
    return (labels[l] || l).slice(0, 2).toUpperCase();
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="h-8 gap-1 rounded-full border border-neutral-300/70 bg-white/80 px-3 text-xs font-semibold text-neutral-700 shadow-sm backdrop-blur hover:bg-white hover:text-neutral-900 supports-[backdrop-filter]:bg-white/60 dark:border-neutral-600 dark:bg-neutral-800/70 dark:text-neutral-200 dark:hover:bg-neutral-700/80 dark:hover:text-white"
                aria-label="Change language"
            >
                <span>{{ shortLabel(current) }}</span>
                <svg class="h-3 w-3 opacity-60" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.17l3.71-2.94a.75.75 0 111.04 1.08l-4.25 3.37a.75.75 0 01-.94 0L5.21 8.29a.75.75 0 01.02-1.08z"
                        clip-rule="evenodd"
                    />
                </svg>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="w-40 rounded-lg border border-neutral-200/80 bg-white/95 p-1 shadow-xl backdrop-blur supports-[backdrop-filter]:bg-white/80 dark:border-neutral-700 dark:bg-neutral-800/95 dark:supports-[backdrop-filter]:bg-neutral-800/80"
            align="end"
            :sideOffset="6"
        >
            <DropdownMenuLabel class="px-2 py-1.5 text-[11px] font-semibold tracking-wide text-neutral-500 dark:text-neutral-400">
                Language
            </DropdownMenuLabel>
            <DropdownMenuSeparator class="bg-neutral-200 dark:bg-neutral-700" />
            <div>
                <DropdownMenuItem
                    v-for="l in availableLocales"
                    :key="l"
                    @select.prevent="switchTo(l)"
                    :data-active="l === current"
                    class="relative flex cursor-pointer select-none items-center gap-2 rounded-md px-2 py-1.5 text-xs font-medium text-neutral-700 outline-none transition hover:bg-neutral-100 focus:bg-neutral-100 data-[active=true]:bg-red-50 data-[active=true]:text-red-700 dark:text-neutral-200 dark:hover:bg-neutral-700/70 dark:focus:bg-neutral-700/70 dark:data-[active=true]:bg-red-500/15 dark:data-[active=true]:text-red-300"
                >
                    <span class="flex-1">{{ labels[l] || l }}</span>
                </DropdownMenuItem>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped>
/* Additional subtle focus ring for accessibility when using keyboard */
:deep([data-radix-dropdown-menu-content]) {
    outline: none;
}
</style>

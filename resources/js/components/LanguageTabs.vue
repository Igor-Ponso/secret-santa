<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Globe2 } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

interface Props {
    locales: string[];
    current: string; // e.g., en, pt_BR, fr
}
const props = defineProps<Props>();
const form = useForm({ locale: props.current });
const { locale } = useI18n();

function writeLocaleCookie(value: string) {
    // 1 year
    document.cookie = `locale=${value};path=/;max-age=${60 * 60 * 24 * 365};SameSite=Lax`;
}

function changeLocale(loc: string) {
    const normalized = loc === 'pt' ? 'pt_BR' : loc;
    if (form.processing || normalized === form.locale) return;
    form.locale = normalized;
    // Optimistic UI: switch immediately
    locale.value = normalized;
    writeLocaleCookie(normalized);
    form.put(route('language.update'), {
        preserveScroll: true,
        onError: () => {
            // revert if server rejected
            // (fallback to previous cookie value via page reload or manual revert)
        },
    });
}

const localeLabels: Record<string, string> = {
    en: 'English',
    pt: 'Português',
    pt_BR: 'Português (Brasil)',
    fr: 'Français',
};
</script>

<template>
    <div class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
        <button
            v-for="loc in props.locales"
            :key="loc"
            @click="changeLocale(loc)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                form.locale === loc
                    ? 'bg-white shadow-sm dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <Globe2 class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ localeLabels[loc] || loc.toUpperCase() }}</span>
        </button>
    </div>
</template>

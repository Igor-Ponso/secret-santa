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

function changeLocale(loc: string) {
    if (form.processing || loc === form.locale) return;
    // Accept clicking on generic 'pt' button if ever passed; normalize here (UI currently passes pt_BR)
    form.locale = loc === 'pt' ? 'pt_BR' : loc;
    // Update cookie via controller (so backend email templates etc. could use it) then update client locale reactively.
    form.put(route('language.update'), {
        preserveScroll: true,
        onSuccess: () => {
            locale.value = form.locale; // vue-i18n reactive switch
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

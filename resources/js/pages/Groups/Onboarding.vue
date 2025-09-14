<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    group: { id: number; name: string };
}
const props = defineProps<Props>();
const { t } = useI18n();

interface DraftItem {
    item: string;
    note?: string;
    url?: string;
}
const drafts = ref<DraftItem[]>([{ item: '', note: '', url: '' }]);

function addRow() {
    if (drafts.value.length < 3) drafts.value.push({ item: '', note: '', url: '' });
}
function removeRow(i: number) {
    if (drafts.value.length > 1) drafts.value.splice(i, 1);
}

function submit() {
    const payload = drafts.value.filter((d) => d.item.trim().length);
    if (!payload.length) return skip();
    router.post(route('groups.onboarding.store', props.group.id), { items: payload });
}
function skip() {
    router.post(route('groups.onboarding.skip', props.group.id));
}
</script>

<template>
    <Head :title="t('onboarding.title')" />
    <AppLayout
        :breadcrumbs="[
            { title: t('common.misc.groups'), href: route('groups.index') },
            { title: props.group.name, href: route('groups.wishlist.index', { group: props.group.id }) },
            { title: t('onboarding.title'), href: route('groups.onboarding.show', { group: props.group.id }) },
        ]"
    >
        <div class="mx-auto max-w-3xl px-2 py-6 md:px-4">
            <div class="mb-8">
                <h1 class="mb-2 text-2xl font-semibold tracking-tight">{{ t('onboarding.title') }}</h1>
                <p class="max-w-prose text-sm text-muted-foreground">{{ t('onboarding.subtitle') }}</p>
            </div>
            <div class="space-y-5">
                <div v-for="(d, i) in drafts" :key="i" class="flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex gap-3">
                        <input
                            v-model="d.item"
                            :placeholder="t('onboarding.item_placeholder')"
                            class="flex-1 rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        />
                        <button
                            v-if="drafts.length > 1"
                            @click="removeRow(i)"
                            type="button"
                            class="rounded-md border px-3 py-2 text-xs font-medium text-destructive transition hover:bg-destructive hover:text-destructive-foreground"
                            aria-label="Remove"
                        >
                            ×
                        </button>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <input
                            v-model="d.note"
                            :placeholder="t('onboarding.note_placeholder')"
                            class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        />
                        <input
                            v-model="d.url"
                            :placeholder="t('onboarding.url_placeholder')"
                            class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        />
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        type="button"
                        @click="addRow"
                        :disabled="drafts.length >= 3"
                        class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent disabled:opacity-40"
                    >
                        {{ t('onboarding.add_item') }}
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        class="inline-flex items-center rounded-md bg-primary px-6 py-2 text-sm font-semibold text-primary-foreground shadow hover:bg-primary/90"
                    >
                        {{ t('onboarding.finish') }}
                    </button>
                    <button
                        type="button"
                        @click="skip"
                        class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                    >
                        {{ t('onboarding.skip') }}
                    </button>
                </div>
                <p class="text-xs text-muted-foreground">{{ t('onboarding.hint_limit') }}</p>
            </div>
        </div>
    </AppLayout>
</template>

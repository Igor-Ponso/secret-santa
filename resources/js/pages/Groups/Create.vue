<script setup lang="ts">
import DateTimePicker from '@/components/DateTimePicker.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const form = useForm({
    name: '',
    description: '',
    min_value: null as number | null,
    max_value: null as number | null,
    draw_at: null as string | null,
});

function submit() {
    form.post(route('groups.store'));
}

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.misc.groups') || 'Groups', href: '/groups' },
    { title: t('common.actions.create') || 'Create', href: '/groups/create' },
];
</script>

<template>
    <Head :title="t('common.actions.create') + ' ' + t('common.misc.groups') || 'Create Group'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl space-y-8 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold tracking-tight">{{ t('common.actions.create') }} {{ t('common.misc.groups') }}</h1>
                <Link :href="route('groups.index')" class="text-sm text-primary hover:underline">{{ t('common.actions.back') }}</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium" required>{{ t('common.labels.name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">{{ t('common.labels.description') }}</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full resize-none rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        :class="{ 'border-destructive': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="min_value" class="text-sm font-medium">{{ t('common.labels.min_value') }}</label>
                        <div class="relative">
                            <input
                                id="min_value"
                                v-model.number="form.min_value"
                                type="number"
                                min="0"
                                placeholder="Ex: 20"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-ring"
                                :class="{ 'border-destructive': form.errors.min_value }"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-muted-foreground">R$</span>
                        </div>
                        <p v-if="form.errors.min_value" class="text-xs text-destructive">{{ form.errors.min_value }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="max_value" class="text-sm font-medium">{{ t('common.labels.max_value') }}</label>
                        <div class="relative">
                            <input
                                id="max_value"
                                v-model.number="form.max_value"
                                type="number"
                                min="0"
                                placeholder="Ex: 100"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-ring"
                                :class="{ 'border-destructive': form.errors.max_value }"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-muted-foreground">R$</span>
                        </div>
                        <p v-if="form.errors.max_value" class="text-xs text-destructive">{{ form.errors.max_value }}</p>
                    </div>
                </div>

                <DateTimePicker
                    v-model="form.draw_at"
                    :label="t('common.labels.draw_date') + ' *'"
                    :required="true"
                    :min="new Date().toISOString()"
                    :placeholder="t('common.misc.choose_draw_date')"
                />
                <p v-if="form.errors.draw_at" class="text-xs text-destructive">{{ form.errors.draw_at }}</p>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ t('common.actions.create') }}
                    </button>
                    <span v-if="form.progress" class="text-xs text-muted-foreground">Uploading: {{ form.progress.percentage }}%</span>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

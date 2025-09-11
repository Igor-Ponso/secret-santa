<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface GroupPayload {
    id: number;
    name: string;
    description: string | null;
    min_value: number | null;
    max_value: number | null;
    draw_at: string | null;
}

const props = defineProps<{ group: GroupPayload }>();

const form = useForm({
    name: props.group.name,
    description: props.group.description ?? '',
    min_value: props.group.min_value,
    max_value: props.group.max_value,
    draw_at: props.group.draw_at ? props.group.draw_at.substring(0, 16) : '',
});

function submit() {
    form.put(route('groups.update', props.group.id));
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Groups', href: '/groups' },
    { title: 'Edit', href: `/groups/${props.group.id}/edit` },
];
</script>

<template>
    <Head :title="`Edit: ${props.group.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl space-y-8 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold tracking-tight">Edit Group</h1>
                <Link :href="route('groups.index')" class="text-sm text-primary hover:underline">Back</Link>
            </div>
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium">Name *</label>
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
                    <label for="description" class="text-sm font-medium">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full resize-none rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        :class="{ 'border-destructive': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="min_value" class="text-sm font-medium">Min Value</label>
                        <input
                            id="min_value"
                            v-model.number="form.min_value"
                            type="number"
                            min="0"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                            :class="{ 'border-destructive': form.errors.min_value }"
                        />
                        <p v-if="form.errors.min_value" class="text-xs text-destructive">{{ form.errors.min_value }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="max_value" class="text-sm font-medium">Max Value</label>
                        <input
                            id="max_value"
                            v-model.number="form.max_value"
                            type="number"
                            min="0"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                            :class="{ 'border-destructive': form.errors.max_value }"
                        />
                        <p v-if="form.errors.max_value" class="text-xs text-destructive">{{ form.errors.max_value }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label for="draw_at" class="text-sm font-medium">Draw Date</label>
                    <input
                        id="draw_at"
                        v-model="form.draw_at"
                        type="datetime-local"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        :class="{ 'border-destructive': form.errors.draw_at }"
                    />
                    <p v-if="form.errors.draw_at" class="text-xs text-destructive">{{ form.errors.draw_at }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

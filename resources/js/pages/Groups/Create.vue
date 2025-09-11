<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    min_value: null as number | null,
    max_value: null as number | null,
    draw_at: '' as string | null,
});

function submit() {
    form.post(route('groups.store'));
}
</script>

<template>
    <div class="mx-auto max-w-xl space-y-8">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold tracking-tight">Create Group</h1>
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
                    Create
                </button>
                <span v-if="form.progress" class="text-xs text-muted-foreground">Uploading: {{ form.progress.percentage }}%</span>
            </div>
        </form>
    </div>
</template>

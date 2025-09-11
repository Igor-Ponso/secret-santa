<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Group {
    id: number;
    name: string;
    description?: string | null;
    min_value?: number | null;
    max_value?: number | null;
    draw_at?: string | null;
    created_at: string;
}

interface Props {
    groups: Group[];
}

const props = defineProps<Props>();

const groupsSorted = computed(() => props.groups);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Groups', href: '/groups' }];
</script>

<template>
    <Head title="Groups" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col space-y-6 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold tracking-tight">Your Groups</h1>
                <Link
                    :href="route('groups.create')"
                    class="inline-flex items-center rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
                    >New Group</Link
                >
            </div>

            <div v-if="groupsSorted.length === 0" class="rounded border border-dashed p-8 text-center text-sm text-muted-foreground">
                You have no groups yet. Create your first one.
            </div>

            <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="g in groupsSorted" :key="g.id" class="group rounded-lg border bg-card p-4 shadow-sm transition hover:shadow">
                    <h2 class="line-clamp-1 font-medium leading-tight">{{ g.name }}</h2>
                    <p v-if="g.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ g.description }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-muted-foreground">
                        <span v-if="g.min_value !== null || g.max_value !== null">Gift: {{ g.min_value ?? 0 }} - {{ g.max_value ?? '∞' }}</span>
                        <span v-if="g.draw_at">Draw: {{ new Date(g.draw_at).toLocaleDateString() }}</span>
                    </div>
                    <div class="mt-3">
                        <Link :href="route('groups.edit', g.id)" class="text-xs text-primary hover:underline">Edit</Link>
                    </div>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { errorToast } from '@/lib/notifications';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const confirmOpen = ref(false);
const pendingId = ref<number | null>(null);

function askDelete(id: number) {
    pendingId.value = id;
    confirmOpen.value = true;
}

function performDelete() {
    if (!pendingId.value) return;
    router.delete(route('groups.destroy', pendingId.value), {
        onError: () => errorToast('Failed to delete group'),
        onFinish: () => {
            confirmOpen.value = false;
            pendingId.value = null;
        },
    });
}
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
                    <div class="mt-3 flex items-center gap-3">
                        <Link :href="route('groups.edit', g.id)" class="text-xs text-primary hover:underline">Edit</Link>
                        <button type="button" @click="askDelete(g.id)" class="text-xs text-destructive hover:underline">Delete</button>
                    </div>
                </li>
            </ul>

            <div v-if="confirmOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                <div class="w-full max-w-sm rounded-lg border bg-card p-5 shadow-lg">
                    <h3 class="text-sm font-semibold">Delete group</h3>
                    <p class="mt-2 text-xs text-muted-foreground">This action cannot be undone. Are you sure?</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-xs hover:bg-accent"
                            @click="
                                confirmOpen = false;
                                pendingId = null;
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-destructive px-3 py-1.5 text-xs font-medium text-destructive-foreground hover:bg-destructive/90"
                            @click="performDelete()"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

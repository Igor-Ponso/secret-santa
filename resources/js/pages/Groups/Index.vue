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
    invitations?: Invitation[];
    wishlist_count?: number; // added
}

interface Invitation {
    email: string;
    status: 'pending' | 'accepted' | 'declined' | 'revoked' | 'expired';
}

interface Props {
    groups: Group[]; // owned
    participating?: Group[]; // groups where user is participant only
}

const props = defineProps<Props>();

const groupsSorted = computed(() => props.groups);
const participatingSorted = computed(() => props.participating ?? []);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Groups', href: '/groups' }];

const confirmOpen = ref(false);
const pendingId = ref<number | null>(null);
const inviteOpen = ref(false);
const inviteGroupId = ref<number | null>(null);
const inviteEmail = ref('');

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

function openInvite(groupId: number) {
    inviteGroupId.value = groupId;
    inviteEmail.value = '';
    inviteOpen.value = true;
}

function submitInvite() {
    if (!inviteGroupId.value) return;
    router.post(
        route('groups.invitations.store', inviteGroupId.value),
        { email: inviteEmail.value },
        {
            onError: () => errorToast('Failed to create invitation'),
            onSuccess: () => {
                inviteOpen.value = false;
                inviteEmail.value = '';
            },
        },
    );
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
                        <span
                            v-if="g.wishlist_count !== undefined"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-300"
                        >
                            🎁 {{ g.wishlist_count }}
                        </span>
                    </div>
                    <div v-if="g.invitations && g.invitations.length" class="mt-3 space-y-1">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Invitations</p>
                        <ul class="space-y-1">
                            <li
                                v-for="inv in g.invitations"
                                :key="inv.email"
                                class="flex items-center justify-between rounded border px-2 py-1 text-[11px]"
                            >
                                <span class="truncate">{{ inv.email }}</span>
                                <span
                                    :class="{
                                        'text-green-600': inv.status === 'accepted',
                                        'text-yellow-600': inv.status === 'pending',
                                        'text-destructive': inv.status === 'declined' || inv.status === 'revoked',
                                        'text-muted-foreground': inv.status === 'expired',
                                    }"
                                    >{{ inv.status }}</span
                                >
                            </li>
                        </ul>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-[10px] font-medium">
                        <Link :href="route('groups.show', g.id)" class="rounded border px-2 py-1 hover:bg-accent">Ver</Link>
                        <Link :href="route('groups.edit', g.id)" class="rounded border px-2 py-1 hover:bg-accent">Editar</Link>
                        <button type="button" @click="openInvite(g.id)" class="rounded border px-2 py-1 hover:bg-accent">Convidar</button>
                        <Link
                            :href="route('groups.wishlist.index', { group: g.id })"
                            class="flex items-center gap-1 rounded border px-2 py-1 hover:bg-accent"
                        >
                            Wishlist
                            <span
                                v-if="g.wishlist_count && g.wishlist_count > 0"
                                class="rounded bg-amber-600/10 px-1 text-[10px] font-semibold text-amber-700 dark:text-amber-300"
                                >{{ g.wishlist_count }}</span
                            >
                        </Link>
                        <button type="button" @click="askDelete(g.id)" class="rounded border px-2 py-1 text-destructive hover:bg-destructive/5">
                            Excluir
                        </button>
                    </div>
                </li>
            </ul>

            <div v-if="participatingSorted.length" class="space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Participando</h2>
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="g in participatingSorted" :key="g.id" class="rounded-lg border bg-card p-4 shadow-sm transition hover:shadow">
                        <h3 class="line-clamp-1 font-medium leading-tight">{{ g.name }}</h3>
                        <p v-if="g.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ g.description }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-muted-foreground">
                            <span v-if="g.min_value !== null || g.max_value !== null">Gift: {{ g.min_value ?? 0 }} - {{ g.max_value ?? '∞' }}</span>
                            <span v-if="g.draw_at">Draw: {{ new Date(g.draw_at).toLocaleDateString() }}</span>
                            <span
                                v-if="g.wishlist_count !== undefined"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-300"
                            >
                                🎁 {{ g.wishlist_count }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-[10px] font-medium">
                            <Link :href="route('groups.show', g.id)" class="rounded border px-2 py-1 hover:bg-accent">Ver</Link>
                            <Link :href="route('groups.wishlist.index', { group: g.id })" class="rounded border px-2 py-1 hover:bg-accent"
                                >Minha Wishlist</Link
                            >
                        </div>
                    </li>
                </ul>
            </div>

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

            <div v-if="inviteOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                <div class="w-full max-w-sm rounded-lg border bg-card p-5 shadow-lg">
                    <h3 class="text-sm font-semibold">Invite to group</h3>
                    <p class="mt-2 text-xs text-muted-foreground">Send an invitation by email.</p>
                    <div class="mt-4 space-y-2">
                        <label class="text-xs font-medium" for="invite_email">Email</label>
                        <input id="invite_email" v-model="inviteEmail" type="email" class="w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-md border px-3 py-1.5 text-xs hover:bg-accent" @click="inviteOpen = false">
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                            @click="submitInvite()"
                        >
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

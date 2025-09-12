<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
// import type { BreadcrumbItem } from '@/types'; // breadcrumb now computed
import { useDateFormat } from '@/lib/formatDate';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

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

const { t } = useI18n();
const { formatDate } = useDateFormat();
const breadcrumbs = computed(() => [{ title: t('common.misc.groups'), href: '/groups' }]);

const confirmOpen = ref(false);
const pendingId = ref<number | null>(null);
const inviteOpen = ref(false);
const inviteGroupId = ref<number | null>(null);
const inviteEmail = ref('');
// Join by code
const joinCode = ref('');
const joining = ref(false);

function submitJoinByCode() {
    if (!joinCode.value.trim()) return;
    joining.value = true;
    router.post(
        route('groups.join_requests.join_by_code'),
        { code: joinCode.value.trim() },
        {
            preserveScroll: true,
            onFinish: () => {
                joining.value = false;
            },
            onSuccess: () => {
                joinCode.value = '';
            },
        },
    );
}

function askDelete(id: number) {
    pendingId.value = id;
    confirmOpen.value = true;
}

function performDelete() {
    if (!pendingId.value) return;
    router.delete(route('groups.destroy', pendingId.value), {
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
            onSuccess: () => {
                inviteOpen.value = false;
                inviteEmail.value = '';
            },
        },
    );
}
</script>

<template>
    <Head :title="t('common.misc.groups') || 'Groups'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col space-y-6 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold tracking-tight">{{ t('common.misc.your_groups') || 'Your Groups' }}</h1>
                <Link
                    :href="route('groups.create')"
                    class="inline-flex items-center rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
                    >{{ t('common.actions.new') }} {{ t('common.misc.groups') }}</Link
                >
            </div>

            <!-- Join by Code -->
            <div class="flex max-w-md flex-col gap-2 rounded border p-4 text-xs">
                <label for="join_code" class="font-medium">{{ t('common.misc.join_by_code') }}</label>
                <div class="flex gap-2">
                    <input
                        id="join_code"
                        v-model="joinCode"
                        @keyup.enter="submitJoinByCode"
                        :placeholder="t('common.misc.join_code_placeholder')"
                        class="flex-1 rounded border px-2 py-1"
                    />
                    <button
                        :disabled="joining || !joinCode"
                        @click="submitJoinByCode"
                        class="rounded bg-primary px-3 py-1 text-primary-foreground disabled:opacity-50"
                    >
                        {{ joining ? '...' : t('common.misc.join_submit') }}
                    </button>
                </div>
                <p class="text-xs text-muted-foreground">{{ t('common.misc.join_help') }}</p>
            </div>

            <div v-if="groupsSorted.length === 0" class="rounded border border-dashed p-8 text-center text-sm text-muted-foreground">
                {{ t('common.misc.no_groups') }}
            </div>

            <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li
                    v-for="g in groupsSorted"
                    :key="g.id"
                    class="group relative cursor-pointer rounded-lg border bg-card p-4 shadow-sm transition hover:border-primary/50 hover:shadow-md"
                    @click="route('groups.show', g.id) && router.get(route('groups.show', g.id))"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="line-clamp-1 font-medium leading-tight group-hover:text-primary">{{ g.name }}</h2>
                            <p v-if="g.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ g.description }}</p>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 transition group-hover:opacity-100" @click.stop>
                            <button
                                type="button"
                                class="rounded p-1 text-muted-foreground hover:text-primary"
                                :aria-label="t('common.actions.edit')"
                                @click="router.get(route('groups.edit', g.id))"
                            >
                                ✏️
                            </button>
                            <button
                                type="button"
                                class="rounded p-1 text-muted-foreground hover:text-destructive"
                                :aria-label="t('common.actions.delete')"
                                @click="askDelete(g.id)"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                        <span v-if="g.min_value !== null || g.max_value !== null"
                            >{{ t('groups.gift_range') }}: {{ g.min_value ?? 0 }} - {{ g.max_value ?? '∞' }}</span
                        >
                        <span v-if="g.draw_at">{{ t('groups.draw_date') }}: {{ formatDate(g.draw_at) }}</span>
                        <span
                            v-if="g.wishlist_count !== undefined"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-300"
                        >
                            🎁 {{ g.wishlist_count }}
                        </span>
                    </div>
                    <!-- Mini invitation status dashboard -->
                    <div v-if="g.invitations && g.invitations.length" class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-md border bg-background/50 p-2">
                            <div class="text-xs font-semibold text-yellow-600">{{ t('groups.metrics_pending') }}</div>
                            <div class="text-sm font-bold">{{ g.invitations.filter((i) => i.status === 'pending').length }}</div>
                        </div>
                        <div class="rounded-md border bg-background/50 p-2">
                            <div class="text-xs font-semibold text-green-600">{{ t('groups.metrics_accepted') }}</div>
                            <div class="text-sm font-bold">{{ g.invitations.filter((i) => i.status === 'accepted').length }}</div>
                        </div>
                        <div class="rounded-md border bg-background/50 p-2">
                            <div class="text-xs font-semibold text-destructive">{{ t('groups.metrics_declined') }}</div>
                            <div class="text-sm font-bold">
                                {{ g.invitations.filter((i) => i.status === 'declined' || i.status === 'revoked').length }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-2 text-[11px] font-medium" @click.stop>
                        <button
                            type="button"
                            @click="openInvite(g.id)"
                            class="inline-flex items-center gap-1 rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm hover:bg-primary/90"
                        >
                            ➕ {{ t('common.actions.invite') }}
                        </button>
                        <Link
                            :href="route('groups.wishlist.index', { group: g.id })"
                            class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-semibold hover:bg-accent"
                        >
                            📝 {{ t('wishlist.title') }}
                            <span
                                v-if="g.wishlist_count && g.wishlist_count > 0"
                                class="rounded bg-amber-600/10 px-1 text-[10px] font-semibold text-amber-700 dark:text-amber-300"
                                >{{ g.wishlist_count }}</span
                            >
                        </Link>
                    </div>
                </li>
            </ul>

            <div v-if="participatingSorted.length" class="space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('common.misc.participating') }}</h2>
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="g in participatingSorted" :key="g.id" class="rounded-lg border bg-card p-4 shadow-sm transition hover:shadow">
                        <h3 class="line-clamp-1 font-medium leading-tight">{{ g.name }}</h3>
                        <p v-if="g.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ g.description }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="g.min_value !== null || g.max_value !== null">Gift: {{ g.min_value ?? 0 }} - {{ g.max_value ?? '∞' }}</span>
                            <span v-if="g.draw_at">{{ t('groups.draw_date') }}: {{ formatDate(g.draw_at) }}</span>
                            <span
                                v-if="g.wishlist_count !== undefined"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-300"
                            >
                                🎁 {{ g.wishlist_count }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-medium">
                            <Link :href="route('groups.show', g.id)" class="rounded border px-2 py-1 hover:bg-accent">{{
                                t('common.actions.view')
                            }}</Link>
                            <Link :href="route('groups.wishlist.index', { group: g.id })" class="rounded border px-2 py-1 hover:bg-accent">{{
                                t('wishlist.title')
                            }}</Link>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-if="confirmOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                <div class="w-full max-w-sm rounded-lg border bg-card p-5 shadow-lg">
                    <h3 class="text-sm font-semibold">{{ t('common.misc.delete_group_title') }}</h3>
                    <p class="mt-2 text-xs text-muted-foreground">{{ t('common.misc.delete_group_desc') }}</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-xs hover:bg-accent"
                            @click="
                                confirmOpen = false;
                                pendingId = null;
                            "
                        >
                            {{ t('common.actions.cancel') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-destructive px-3 py-1.5 text-xs font-medium text-destructive-foreground hover:bg-destructive/90"
                            @click="performDelete()"
                        >
                            {{ t('common.actions.delete') }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="inviteOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                <div class="w-full max-w-sm rounded-lg border bg-card p-5 shadow-lg">
                    <h3 class="text-sm font-semibold">{{ t('common.misc.invite_group_title') }}</h3>
                    <p class="mt-2 text-xs text-muted-foreground">{{ t('common.misc.invite_group_desc') }}</p>
                    <div class="mt-4 space-y-2">
                        <label class="text-xs font-medium" for="invite_email">{{ t('common.labels.email') }}</label>
                        <input id="invite_email" v-model="inviteEmail" type="email" class="w-full rounded-md border px-3 py-2 text-sm" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-md border px-3 py-1.5 text-xs hover:bg-accent" @click="inviteOpen = false">
                            {{ t('common.actions.cancel') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                            @click="submitInvite()"
                        >
                            {{ t('common.actions.send') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

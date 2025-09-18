<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
// import { type BreadcrumbItem } from '@/types'; // not needed after reactive refactor using computed only
import { useDateFormat } from '@/lib/formatDate';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface GroupSummary {
    id: number;
    name: string;
    draw_at?: string | null;
}
interface InvitationSummary {
    id?: number; // if internal ID available in future
    group: { id: number; name: string };
    email: string;
    token?: string; // plain token when belongs to this user
    expires_at?: string | null;
}
interface ActivityItem {
    message: string;
    date: string;
}

interface ReadinessItem {
    id: number;
    name: string;
    coverage: number; // percent
    participants: number;
    ready: boolean;
    threshold: number;
}

interface DashboardProps {
    groupsCount: number;
    pendingInvitationsCount: number;
    upcomingDraws: GroupSummary[];
    pendingInvitations: InvitationSummary[];
    recentActivities: ActivityItem[];
    pendingJoinRequests: { id: number; group: { id: number; name: string }; requested_at: string }[];
    readiness: ReadinessItem[];
}

const props = defineProps<DashboardProps>();

// Reactive invitations list (mutable client-side after actions)
interface LocalInvitation extends InvitationSummary {
    loading?: boolean;
}
const invitations = ref<LocalInvitation[]>([...props.pendingInvitations]);
const pendingCount = computed(() => invitations.value.length);

function mutateInvitation(id?: number, cb?: (inv: LocalInvitation) => void) {
    if (!id) return;
    const idx = invitations.value.findIndex((i) => i.id === id);
    if (idx === -1) return;
    if (cb) cb(invitations.value[idx]);
}

function removeInvitation(id?: number) {
    if (!id) return;
    invitations.value = invitations.value.filter((i) => i.id !== id);
}

function act(inv: LocalInvitation, action: 'accept' | 'decline') {
    if (!inv.id || inv.loading) return;
    mutateInvitation(inv.id, (i) => {
        i.loading = true;
    });
    const routeName = action === 'accept' ? 'invites.auth.accept' : 'invites.auth.decline';
    router.post(
        route(routeName, inv.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => removeInvitation(inv.id),
            onError: () => {
                mutateInvitation(inv.id, (i) => {
                    i.loading = false;
                });
            },
        },
    );
}

const { t } = useI18n();
const { formatDate, formatDateTime } = useDateFormat();
const breadcrumbs = computed(() => [
    {
        title: t('dashboard.heading'),
        href: '/dashboard',
    },
]);

const readinessSorted = computed(() => {
    return [...props.readiness].sort((a, b) => Number(b.ready) - Number(a.ready) || b.coverage - a.coverage);
});
</script>

<template>
    <Head :title="t('dashboard.heading') || 'Dashboard'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Resumo -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">{{ t('dashboard.summary') }}</h2>
                    <div class="space-y-1 text-xs">
                        <div>
                            <span class="font-bold">{{ props.groupsCount }}</span> {{ t('dashboard.groups_count_label') }}
                        </div>
                        <div>
                            <span class="font-bold">{{ pendingCount }}</span> {{ t('dashboard.pending_invites_count_label') }}
                        </div>
                        <div>
                            <span class="font-bold">{{ props.upcomingDraws.length }}</span> {{ t('dashboard.upcoming_draws_count_label') }}
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <Link
                            :href="route('groups.create')"
                            class="rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90"
                            >{{ t('common.actions.new') }} {{ t('common.misc.groups') }}</Link
                        >
                        <Link :href="route('groups.index')" class="rounded border px-3 py-1.5 text-xs hover:bg-accent"
                            >{{ t('common.actions.view') }} {{ t('common.misc.groups') }}</Link
                        >
                    </div>
                </div>
                <!-- Próximos Sorteios -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">{{ t('common.misc.upcoming_draws') }}</h2>
                    <ul v-if="props.upcomingDraws.length" class="space-y-1 text-xs">
                        <li v-for="g in props.upcomingDraws" :key="g.id">
                            <span class="font-medium">{{ g.name }}</span> —
                            {{ g.draw_at ? formatDate(g.draw_at) : t('common.misc.none') }}
                        </li>
                    </ul>
                    <div v-else class="text-xs text-muted-foreground">{{ t('common.misc.no_upcoming_draws') }}</div>
                </div>
                <!-- Convites Pendentes -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">{{ t('common.misc.pending_invitations') }}</h2>
                    <ul v-if="invitations.length" class="space-y-2 text-xs">
                        <li v-for="inv in invitations" :key="inv.group.id + '-' + inv.email" class="flex flex-col gap-1 rounded border p-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="font-medium">{{ inv.group.name }}</span>
                                    <span class="text-muted-foreground"> — {{ inv.email }}</span>
                                    <span v-if="inv.expires_at" class="ml-1 text-muted-foreground"
                                        >({{ t('common.misc.expires_on') }} {{ formatDate(inv.expires_at) }})</span
                                    >
                                </div>
                                <div class="flex gap-2">
                                    <template v-if="inv.id">
                                        <button
                                            type="button"
                                            @click="act(inv, 'accept')"
                                            :disabled="inv.loading"
                                            class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white hover:bg-green-500 disabled:opacity-50"
                                        >
                                            {{ inv.loading ? '...' : t('common.actions.add') /* reuse or create accept key */ }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="act(inv, 'decline')"
                                            :disabled="inv.loading"
                                            class="rounded bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                                        >
                                            {{ inv.loading ? '...' : t('common.actions.delete') /* placeholder for decline */ }}
                                        </button>
                                    </template>
                                    <span v-else class="text-xs text-muted-foreground">Email action</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="text-xs text-muted-foreground">{{ t('common.misc.no_pending_invitations') }}</div>
                </div>
                <!-- Pedidos de Entrada Pendentes -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">{{ t('common.misc.pending_join_requests') || 'Pedidos de Entrada Pendentes' }}</h2>
                    <ul v-if="props.pendingJoinRequests.length" class="space-y-2 text-xs">
                        <li v-for="jr in props.pendingJoinRequests" :key="jr.id" class="flex flex-col gap-1 rounded border p-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="font-medium">{{ jr.group.name }}</span>
                                    <span class="ml-1 text-muted-foreground">— {{ formatDate(jr.requested_at) }}</span>
                                </div>
                                <Link :href="route('groups.show', jr.group.id)" class="rounded border px-2 py-1 text-xs hover:bg-accent">{{
                                    t('common.actions.view')
                                }}</Link>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="text-xs text-muted-foreground">{{ t('common.misc.none') }}</div>
                </div>
                <!-- Readiness Overview -->
                <div v-if="props.readiness.length" class="rounded-xl border bg-card p-4 md:col-span-3">
                    <h2 class="mb-3 text-sm font-semibold">{{ t('common.misc.readiness_overview') || 'Readiness Overview' }}</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[500px] border-collapse text-xs">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-1 pr-2 font-medium">{{ t('common.misc.group') || 'Group' }}</th>
                                    <th class="py-1 pr-2 font-medium">{{ t('common.misc.participants') || 'Participants' }}</th>
                                    <th class="py-1 pr-2 font-medium">{{ t('common.misc.wishlist_coverage') || 'Wishlist %' }}</th>
                                    <th class="py-1 pr-2 font-medium">{{ t('common.misc.status') || 'Status' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in readinessSorted" :key="g.id" class="border-b last:border-b-0 hover:bg-accent/40">
                                    <td class="py-1 pr-2">
                                        <Link :href="route('groups.show', g.id)" class="font-medium hover:underline">{{ g.name }}</Link>
                                    </td>
                                    <td class="py-1 pr-2">{{ g.participants }}</td>
                                    <td class="py-1 pr-2">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-32 overflow-hidden rounded bg-muted">
                                                <div
                                                    class="h-full bg-primary transition-all"
                                                    :class="g.coverage >= g.threshold ? 'bg-green-600' : 'bg-primary'"
                                                    :style="{ width: g.coverage + '%' }"
                                                />
                                            </div>
                                            <span>{{ g.coverage }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-1 pr-2">
                                        <span
                                            class="inline-flex items-center rounded px-2 py-0.5"
                                            :class="[
                                                g.ready
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-300'
                                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
                                            ]"
                                        >
                                            {{ g.ready ? t('common.misc.ready') || 'Ready' : t('common.misc.not_ready') || 'Not Ready' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-[10px] text-muted-foreground">
                        {{ t('common.misc.readiness_threshold_hint') || 'Ready when at least 2 participants and wishlist coverage meets threshold.' }}
                    </p>
                </div>
            </div>
            <!-- Atividades Recentes -->
            <div class="rounded-xl border bg-card p-4">
                <h2 class="mb-2 text-sm font-semibold">{{ t('common.misc.recent_activity') }}</h2>
                <ul v-if="props.recentActivities.length" class="space-y-1 text-xs">
                    <li v-for="a in props.recentActivities" :key="a.date + '-' + a.message">
                        <span class="text-muted-foreground">{{ formatDateTime(a.date) }}:</span> {{ a.message }}
                    </li>
                </ul>
                <div v-else class="text-xs text-muted-foreground">{{ t('common.misc.no_recent_activity') }}</div>
            </div>
        </div>
    </AppLayout>
</template>

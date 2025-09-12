<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { errorToast, successToast } from '@/lib/notifications';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

interface DashboardProps {
    groupsCount: number;
    pendingInvitationsCount: number;
    upcomingDraws: GroupSummary[];
    pendingInvitations: InvitationSummary[];
    recentActivities: ActivityItem[];
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
            onSuccess: () => {
                removeInvitation(inv.id);
                if (action === 'accept') successToast('Convite aceito');
                else successToast('Convite recusado');
            },
            onError: () => {
                mutateInvitation(inv.id, (i) => {
                    i.loading = false;
                });
                errorToast('Falha ao processar convite');
            },
        },
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Resumo -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">Resumo</h2>
                    <div class="space-y-1 text-xs">
                        <div>
                            <span class="font-bold">{{ props.groupsCount }}</span> grupos
                        </div>
                        <div>
                            <span class="font-bold">{{ pendingCount }}</span> convites pendentes
                        </div>
                        <div>
                            <span class="font-bold">{{ props.upcomingDraws.length }}</span> sorteios futuros
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <Link
                            :href="route('groups.create')"
                            class="rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90"
                            >Novo Grupo</Link
                        >
                        <Link :href="route('groups.index')" class="rounded border px-3 py-1.5 text-xs hover:bg-accent">Ver Grupos</Link>
                    </div>
                </div>
                <!-- Próximos Sorteios -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">Próximos Sorteios</h2>
                    <ul v-if="props.upcomingDraws.length" class="space-y-1 text-xs">
                        <li v-for="g in props.upcomingDraws" :key="g.id">
                            <span class="font-medium">{{ g.name }}</span> — {{ g.draw_at ? new Date(g.draw_at).toLocaleDateString() : 'Sem data' }}
                        </li>
                    </ul>
                    <div v-else class="text-xs text-muted-foreground">Nenhum sorteio futuro.</div>
                </div>
                <!-- Convites Pendentes -->
                <div class="rounded-xl border bg-card p-4">
                    <h2 class="mb-2 text-sm font-semibold">Convites Pendentes</h2>
                    <ul v-if="invitations.length" class="space-y-2 text-xs">
                        <li v-for="inv in invitations" :key="inv.group.id + '-' + inv.email" class="flex flex-col gap-1 rounded border p-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="font-medium">{{ inv.group.name }}</span>
                                    <span class="text-muted-foreground"> — {{ inv.email }}</span>
                                    <span v-if="inv.expires_at" class="ml-1 text-muted-foreground"
                                        >(expira {{ new Date(inv.expires_at).toLocaleDateString() }})</span
                                    >
                                </div>
                                <div class="flex gap-2">
                                    <template v-if="inv.id">
                                        <button
                                            type="button"
                                            @click="act(inv, 'accept')"
                                            :disabled="inv.loading"
                                            class="rounded bg-green-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-green-500 disabled:opacity-50"
                                        >
                                            {{ inv.loading ? '...' : 'Aceitar' }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="act(inv, 'decline')"
                                            :disabled="inv.loading"
                                            class="rounded bg-red-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                                        >
                                            {{ inv.loading ? '...' : 'Recusar' }}
                                        </button>
                                    </template>
                                    <span v-else class="text-[10px] text-muted-foreground">Ação via email</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="text-xs text-muted-foreground">Nenhum convite pendente.</div>
                </div>
            </div>
            <!-- Atividades Recentes -->
            <div class="rounded-xl border bg-card p-4">
                <h2 class="mb-2 text-sm font-semibold">Atividades Recentes</h2>
                <ul v-if="props.recentActivities.length" class="space-y-1 text-xs">
                    <li v-for="a in props.recentActivities" :key="a.date + '-' + a.message">
                        <span class="text-muted-foreground">{{ new Date(a.date).toLocaleString() }}:</span> {{ a.message }}
                    </li>
                </ul>
                <div v-else class="text-xs text-muted-foreground">Nenhuma atividade recente.</div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface GroupSummary {
    id: number;
    name: string;
    draw_at?: string | null;
}
interface InvitationSummary {
    group: { id: number; name: string };
    email: string;
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
                            <span class="font-bold">{{ props.pendingInvitationsCount }}</span> convites pendentes
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
                    <ul v-if="props.pendingInvitations.length" class="space-y-1 text-xs">
                        <li v-for="inv in props.pendingInvitations" :key="inv.group.id + '-' + inv.email">
                            <span class="font-medium">{{ inv.group.name }}</span> — {{ inv.email }}
                            <span v-if="inv.expires_at" class="ml-1 text-muted-foreground"
                                >(expira {{ new Date(inv.expires_at).toLocaleDateString() }})</span
                            >
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

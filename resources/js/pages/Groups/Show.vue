<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { errorToast, successToast } from '@/lib/notifications';
import { Head, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Recipient {
    id: number;
    name: string;
}
interface ShowProps {
    group: any; // simplified typing for brevity after adding meta
}

const props = defineProps<ShowProps>();
// Computed wrapper so when Inertia replaces props the template reacts
const group = computed(() => props.group);
// Tabs
const activeTab = ref<'participants' | 'invitations' | 'join_requests'>('participants');
// Local participant client-side search
const participantSearch = ref('');
const filteredParticipants = computed(() => {
    const list = group.value.participants || [];
    if (!participantSearch.value.trim()) return list;
    const q = participantSearch.value.toLowerCase();
    return list.filter((p: any) => p.name?.toLowerCase().includes(q));
});

function cap(v: string) {
    return v ? v.charAt(0).toUpperCase() + v.slice(1) : v;
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'accepted':
        case 'approved':
            return 'bg-green-600 text-white hover:bg-green-600/90';
        case 'pending':
            return 'bg-amber-500 text-white hover:bg-amber-500/90';
        case 'declined':
        case 'revoked':
        case 'denied':
            return 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
        case 'expired':
            return 'bg-muted text-muted-foreground';
        default:
            return 'bg-secondary text-secondary-foreground';
    }
}

// Invitation pagination/search state
const inviteSearch = ref(group.value.invitations_meta?.search || '');
const jrSearch = ref(group.value.join_requests_meta?.search || '');
let inviteSearchTimeout: any = null;
let jrSearchTimeout: any = null;

function updateQuery(params: Record<string, any>) {
    router.get(route('groups.show', group.value.id), params, {
        only: ['group'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function onInvitePage(page: number) {
    updateQuery({
        invite_page: page,
        invite_search: inviteSearch.value,
        jr_page: group.value.join_requests_meta?.current_page || 1,
        jr_search: jrSearch.value,
    });
}
function onJrPage(page: number) {
    updateQuery({
        invite_page: group.value.invitations_meta?.current_page || 1,
        invite_search: inviteSearch.value,
        jr_page: page,
        jr_search: jrSearch.value,
    });
}

// Debounced search for invitations
watch(inviteSearch, () => {
    clearTimeout(inviteSearchTimeout);
    inviteSearchTimeout = setTimeout(() => {
        onInvitePage(1);
    }, 300);
});
// Debounced search for join requests
watch(jrSearch, () => {
    clearTimeout(jrSearchTimeout);
    jrSearchTimeout = setTimeout(() => {
        onJrPage(1);
    }, 300);
});

function copyJoinCode() {
    if (!group.value.join_code) return;
    navigator.clipboard.writeText(group.value.join_code);
    successToast('Código copiado');
}

function approveJoin(id: number) {
    joinRequestActing.value = { id, action: 'approve' };
    router.post(
        route('groups.join_requests.approve', { group: group.value.id, joinRequest: id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => (joinRequestActing.value = { id: null, action: null }),
        },
    );
}
function denyJoin(id: number) {
    joinRequestActing.value = { id, action: 'deny' };
    router.post(
        route('groups.join_requests.deny', { group: group.value.id, joinRequest: id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => (joinRequestActing.value = { id: null, action: null }),
        },
    );
}
const recipient = ref<Recipient | null>(null);
const recipientWishlist = ref<{ id: number; item: string; url?: string | null; note?: string | null }[]>([]);
const loadingRecipient = ref(false);
const drawing = ref(false);
const actingOn = ref<number | null>(null);
const joinRequestActing = ref<{ id: number | null; action: 'approve' | 'deny' | null }>({ id: null, action: null });
const joinCodeRegenerating = ref(false);
const userId = (window as any).Laravel?.user?.id; // assuming provided globally
const dialogMode = ref<'resend' | 'revoke' | null>(null);
const dialogInvitationId = ref<number | null>(null);
const dialogOpen = ref(false);

function openDialog(mode: 'resend' | 'revoke', invId: number) {
    dialogMode.value = mode;
    dialogInvitationId.value = invId;
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
    // small timeout to allow close animation before clearing
    setTimeout(() => {
        dialogMode.value = null;
        dialogInvitationId.value = null;
    }, 150);
}

function performDialogAction() {
    if (!dialogInvitationId.value || !dialogMode.value) return;
    actingOn.value = dialogInvitationId.value;
    const id = dialogInvitationId.value;
    const mode = dialogMode.value;
    const routeName = mode === 'resend' ? 'groups.invitations.resend' : 'groups.invitations.revoke';
    router.post(
        route(routeName, { group: group.value.id, invitation: id }),
        {},
        {
            preserveScroll: true,
            onError: () => errorToast(mode === 'resend' ? 'Falha ao reenviar' : 'Falha ao revogar'),
            onSuccess: () => {
                if (mode === 'resend') {
                    successToast('Convite reenviado');
                } else {
                    successToast('Convite revogado');
                }
            },
            onFinish: () => {
                actingOn.value = null;
                closeDialog();
            },
        },
    );
}

function fetchRecipient() {
    if (!props.group.has_draw) return;
    loadingRecipient.value = true;
    fetch(`/groups/${props.group.id}/recipient`, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((data) => {
            recipient.value = data.data?.user || null;
            recipientWishlist.value = data.data?.wishlist || [];
        })
        .finally(() => (loadingRecipient.value = false));
}

function runDraw() {
    drawing.value = true;
    router.post(
        route('groups.draw.run', props.group.id),
        {},
        {
            onSuccess: () => {
                successToast('Sorteio realizado');
                fetchRecipient();
            },
            onError: () => {
                errorToast('Falha ao sortear');
            },
            onFinish: () => {
                drawing.value = false;
            },
        },
    );
}

function regenerateJoinCode() {
    if (joinCodeRegenerating.value) return;
    joinCodeRegenerating.value = true;
    router.post(
        route('groups.regenerate_code', group.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => successToast(group.value.join_code ? 'Novo código gerado' : 'Código criado'),
            onError: () => errorToast('Falha ao gerar código'),
            onFinish: () => (joinCodeRegenerating.value = false),
        },
    );
}

onMounted(fetchRecipient);
</script>

<template>
    <Head :title="group.name" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Grupos', href: route('groups.index') },
            { title: group.name, href: '' },
        ]"
    >
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ group.name }}</h1>
                <p v-if="group.description" class="max-w-prose text-sm text-muted-foreground">{{ group.description }}</p>
            </div>

            <div class="space-y-4 rounded border p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Sorteio</h2>
                    <div v-if="group.is_owner && !group.has_draw">
                        <button
                            @click="runDraw"
                            :disabled="drawing || !group.can_draw"
                            class="flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            <LoaderCircle v-if="drawing" class="h-4 w-4 animate-spin" />
                            {{ drawing ? 'Sorteando...' : group.can_draw ? 'Executar Sorteio' : 'Aguardando Participantes' }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                    <span class="inline-flex items-center gap-1 rounded bg-accent/50 px-2 py-0.5">Participantes: {{ group.participant_count }}</span>
                    <span v-if="!group.has_draw && group.participant_count < 2" class="text-destructive">Mínimo 2 participantes para sortear.</span>
                    <span v-if="group.has_draw" class="text-green-600 dark:text-green-400">Sorteio concluído</span>
                </div>
                <div v-if="group.has_draw" class="space-y-2">
                    <p class="text-xs text-muted-foreground">Seu amigo secreto:</p>
                    <div v-if="loadingRecipient" class="text-xs">Carregando...</div>
                    <div v-else-if="recipient" class="flex flex-col gap-2 rounded bg-accent px-3 py-2 text-sm font-medium">
                        <span>{{ recipient.name }}</span>
                        <div v-if="recipientWishlist.length" class="rounded border bg-background/70 p-2">
                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Wishlist</p>
                            <ul class="max-h-40 space-y-1 overflow-auto pr-1">
                                <li v-for="w in recipientWishlist" :key="w.id" class="rounded bg-accent/40 px-2 py-1 text-[11px] leading-tight">
                                    <span class="font-medium">{{ w.item }}</span>
                                    <a v-if="w.url" :href="w.url" target="_blank" rel="noopener" class="ml-2 text-[10px] underline hover:text-primary"
                                        >link</a
                                    >
                                    <div v-if="w.note" class="mt-0.5 text-[10px] italic opacity-80">{{ w.note }}</div>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="text-[10px] text-muted-foreground">Sem itens na wishlist.</div>
                    </div>
                    <div v-else class="text-xs text-muted-foreground">Não encontrado (verifique se você participa do grupo).</div>
                </div>
                <div v-else class="text-xs text-muted-foreground">Ainda não sorteado.</div>
            </div>

            <!-- Owner overview metrics -->
            <div v-if="group.is_owner && group.metrics" class="rounded border p-4 text-[11px]">
                <h2 class="mb-2 text-sm font-semibold">Visão Geral</h2>
                <div class="grid gap-3 sm:grid-cols-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium">Pendentes</span><span class="text-lg font-bold">{{ group.metrics.pending }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium">Aceitos</span
                        ><span class="text-lg font-bold text-green-600">{{ group.metrics.accepted }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium">Recusados</span
                        ><span class="text-lg font-bold text-destructive">{{ group.metrics.declined }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium">Revogados</span
                        ><span class="text-lg font-bold text-destructive">{{ group.metrics.revoked }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabs navigation -->
            <div class="flex gap-2 overflow-x-auto border-b pb-2 text-xs">
                <button
                    :class="['rounded px-3 py-1', activeTab === 'participants' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
                    @click="activeTab = 'participants'"
                >
                    Participantes
                </button>
                <button
                    v-if="group.is_owner"
                    :class="['rounded px-3 py-1', activeTab === 'invitations' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
                    @click="activeTab = 'invitations'"
                >
                    Convites
                </button>
                <button
                    v-if="group.is_owner"
                    :class="['rounded px-3 py-1', activeTab === 'join_requests' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
                    @click="activeTab = 'join_requests'"
                >
                    Pedidos
                    <span v-if="group.pending_join_requests_count" class="ml-1 rounded bg-destructive px-1 text-[10px] text-destructive-foreground">{{
                        group.pending_join_requests_count
                    }}</span>
                </button>
                <div v-if="group.is_owner" class="ml-auto flex items-center gap-2 text-[10px]">
                    <span v-if="group.join_code" class="rounded bg-accent px-2 py-0.5 font-mono">{{ group.join_code }}</span>
                    <button
                        v-if="group.join_code"
                        class="rounded bg-accent px-2 py-0.5 disabled:opacity-50"
                        :disabled="joinCodeRegenerating"
                        @click="copyJoinCode"
                    >
                        Copiar
                    </button>
                    <button
                        class="flex items-center gap-1 rounded bg-primary px-2 py-0.5 text-primary-foreground disabled:opacity-50"
                        :disabled="joinCodeRegenerating"
                        @click="regenerateJoinCode"
                    >
                        <LoaderCircle v-if="joinCodeRegenerating" class="h-3 w-3 animate-spin" />
                        {{ group.join_code ? 'Novo' : 'Gerar Código' }}
                    </button>
                </div>
            </div>

            <!-- Participants Tab -->
            <div v-show="activeTab === 'participants'" class="space-y-3 rounded border p-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold">Participantes ({{ group.participant_count }})</h2>
                    <input v-model="participantSearch" placeholder="Buscar" class="h-7 w-40 rounded border px-2 text-xs" />
                </div>
                <ul v-if="filteredParticipants.length" class="space-y-1 text-sm">
                    <li
                        v-for="p in filteredParticipants"
                        :key="p.id"
                        class="flex items-center justify-between gap-2 rounded bg-accent/40 px-2 py-1 text-[11px]"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-block h-5 w-5 shrink-0 rounded-full bg-primary/20 text-center text-[10px] font-medium leading-5">{{
                                p.name[0]?.toUpperCase()
                            }}</span>
                            <span class="truncate">{{ p.name }}</span>
                            <span v-if="p.id === userId" class="rounded bg-primary/10 px-1 py-0.5 text-[9px] uppercase tracking-wide">Você</span>
                            <span
                                v-if="p.wishlist_count"
                                class="rounded bg-amber-500/20 px-1 py-0.5 text-[9px] font-medium text-amber-700 dark:text-amber-300"
                                >🎁 {{ p.wishlist_count }}</span
                            >
                        </div>
                        <div v-if="p.accepted_at" class="flex items-center gap-2 text-[9px]">
                            <Badge class="bg-green-600 text-white hover:bg-green-600/90">
                                Aceito em {{ new Date(p.accepted_at).toLocaleString() }}
                            </Badge>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-[11px] text-muted-foreground">Nenhum participante ainda.</p>
            </div>

            <!-- Invitations Tab -->
            <div v-if="group.is_owner" v-show="activeTab === 'invitations'" class="space-y-3 rounded border p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Convites</h2>
                    <input v-model="inviteSearch" placeholder="Buscar email" class="h-7 rounded border px-2 text-xs" />
                </div>
                <ul v-if="group.invitations && group.invitations.length" class="space-y-1 text-sm">
                    <li
                        v-for="inv in group.invitations"
                        :key="inv.id"
                        class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-[11px]"
                    >
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-medium" :title="inv.email">{{ inv.email }}</span>
                            <span class="mt-0.5 inline-flex flex-wrap items-center gap-2 text-[10px]">
                                <Badge :class="statusBadgeClass(inv.status)">{{ cap(inv.status) }}</Badge>
                                <span
                                    v-if="inv.expires_at && inv.status === 'pending'"
                                    class="text-[9px] text-muted-foreground"
                                    :title="inv.expires_at"
                                >
                                    expira em {{ new Date(inv.expires_at).toLocaleDateString() }}
                                </span>
                                <span v-if="inv.created_at" class="text-[9px] text-muted-foreground"
                                    >enviado {{ new Date(inv.created_at).toLocaleDateString() }}</span
                                >
                                <span v-if="inv.accepted_at" class="text-[9px] text-green-600"
                                    >Aceito {{ new Date(inv.accepted_at).toLocaleDateString() }}</span
                                >
                                <span v-if="inv.declined_at" class="text-[9px] text-destructive"
                                    >Recusado {{ new Date(inv.declined_at).toLocaleDateString() }}</span
                                >
                                <span v-if="inv.revoked_at" class="text-[9px] text-destructive"
                                    >Revogado {{ new Date(inv.revoked_at).toLocaleDateString() }}</span
                                >
                            </span>
                        </div>
                        <div class="flex items-center gap-1" v-if="inv.status === 'pending' || inv.status === 'revoked'">
                            <button
                                @click.prevent="openDialog('resend', inv.id)"
                                :disabled="actingOn === inv.id"
                                class="rounded bg-accent px-2 py-0.5 text-[10px] hover:bg-accent/70 disabled:opacity-50"
                            >
                                Reenviar
                            </button>
                            <button
                                @click.prevent="openDialog('revoke', inv.id)"
                                :disabled="actingOn === inv.id"
                                class="rounded bg-destructive px-2 py-0.5 text-[10px] text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                            >
                                Revogar
                            </button>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-[11px] text-muted-foreground">Nenhum convite.</p>
                <div v-if="group.invitations_meta && group.invitations_meta.last_page > 1" class="pt-2">
                    <Pagination
                        :page="group.invitations_meta.current_page"
                        :items-per-page="group.invitations_meta.per_page"
                        :total="group.invitations_meta.total"
                        @update:page="onInvitePage"
                    />
                </div>
            </div>

            <!-- Join Requests Tab -->
            <div v-if="group.is_owner" v-show="activeTab === 'join_requests'" class="space-y-3 rounded border p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Pedidos de Entrada</h2>
                    <input v-model="jrSearch" placeholder="Buscar usuário" class="h-7 rounded border px-2 text-xs" />
                </div>
                <ul v-if="group.join_requests && group.join_requests.length" class="space-y-1 text-sm">
                    <li
                        v-for="jr in group.join_requests"
                        :key="jr.id"
                        class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-[11px]"
                    >
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-medium" :title="jr.user?.email">{{ jr.user?.name || 'Usuário' }}</span>
                            <span class="text-[10px] text-muted-foreground">{{ jr.user?.email }}</span>
                        </div>
                        <div class="flex items-center gap-1" v-if="jr.status === 'pending'">
                            <button
                                @click.prevent="approveJoin(jr.id)"
                                :disabled="joinRequestActing.id === jr.id"
                                class="flex items-center gap-1 rounded bg-green-600 px-2 py-0.5 text-[10px] text-white hover:bg-green-700 disabled:opacity-50"
                            >
                                <LoaderCircle
                                    v-if="joinRequestActing.id === jr.id && joinRequestActing.action === 'approve'"
                                    class="h-3 w-3 animate-spin"
                                />
                                Aceitar
                            </button>
                            <button
                                @click.prevent="denyJoin(jr.id)"
                                :disabled="joinRequestActing.id === jr.id"
                                class="flex items-center gap-1 rounded bg-destructive px-2 py-0.5 text-[10px] text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                            >
                                <LoaderCircle
                                    v-if="joinRequestActing.id === jr.id && joinRequestActing.action === 'deny'"
                                    class="h-3 w-3 animate-spin"
                                />
                                Recusar
                            </button>
                        </div>
                        <span v-else class="inline-flex flex-wrap items-center gap-2">
                            <Badge :class="statusBadgeClass(jr.status)">{{ cap(jr.status) }}</Badge>
                            <span class="text-[9px] text-muted-foreground" v-if="jr.created_at"
                                >Enviado {{ new Date(jr.created_at).toLocaleDateString() }}</span
                            >
                            <span class="text-[9px] text-green-600" v-if="jr.approved_at"
                                >Aprovado {{ new Date(jr.approved_at).toLocaleDateString() }}</span
                            >
                            <span class="text-[9px] text-destructive" v-if="jr.denied_at"
                                >Recusado {{ new Date(jr.denied_at).toLocaleDateString() }}</span
                            >
                        </span>
                    </li>
                </ul>
                <p v-else class="text-[11px] text-muted-foreground">Nenhum pedido.</p>
                <div v-if="group.join_requests_meta && group.join_requests_meta.last_page > 1" class="pt-2">
                    <Pagination
                        :page="group.join_requests_meta.current_page"
                        :items-per-page="group.join_requests_meta.per_page"
                        :total="group.join_requests_meta.total"
                        @update:page="onJrPage"
                    />
                </div>
            </div>

            <p class="mt-2 text-[10px] text-muted-foreground">Após o sorteio, cada participante vê apenas seu destinatário e a wishlist associada.</p>
        </div>

        <!-- Single shared AlertDialog instance -->
        <AlertDialog
            v-if="dialogMode"
            :open="dialogOpen"
            @update:open="
                (v: any) => {
                    if (!v) closeDialog();
                }
            "
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ dialogMode === 'revoke' ? 'Revogar convite?' : 'Reenviar convite?' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        <template v-if="dialogMode === 'revoke'">
                            Essa ação impedirá que o convidado aceite o convite existente. Você poderá criar outro depois.
                        </template>
                        <template v-else>
                            Um novo token será gerado e o anterior se torna inválido. Garanta que vai reenviar o link atualizado por e-mail.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeDialog" class="text-xs">Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="performDialogAction" :disabled="actingOn !== null" class="flex items-center gap-2 text-xs">
                        <LoaderCircle v-if="actingOn !== null" class="h-4 w-4 animate-spin" />
                        {{ dialogMode === 'revoke' ? 'Revogar' : 'Reenviar' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { errorToast, successToast } from '@/lib/notifications';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Recipient {
    id: number;
    name: string;
}
interface ShowProps {
    group: {
        id: number;
        name: string;
        description?: string | null;
        is_owner: boolean;
        has_draw: boolean;
        participant_count: number;
        can_draw: boolean;
        participants: { id: number; name: string }[];
        invitations?: { id: number; email: string; status: string; expires_at?: string | null }[];
        metrics?: { pending: number; accepted: number; declined: number; revoked: number };
    };
}

const props = defineProps<ShowProps>();
// Expose group directly for template binding
const group = props.group;
const recipient = ref<Recipient | null>(null);
const recipientWishlist = ref<{ id: number; item: string; url?: string | null; note?: string | null }[]>([]);
const loadingRecipient = ref(false);
const drawing = ref(false);
const actingOn = ref<number | null>(null);
const userId = (window as any).Laravel?.user?.id; // assuming provided globally

function resend(inv: { id: number }) {
    actingOn.value = inv.id;
    router.post(
        route('groups.invitations.resend', { group: group.id, invitation: inv.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => successToast('Convite reenviado'),
            onError: () => errorToast('Falha ao reenviar'),
            onFinish: () => (actingOn.value = null),
        },
    );
}

function revoke(inv: { id: number }) {
    if (!confirm('Revogar este convite?')) return;
    actingOn.value = inv.id;
    router.post(
        route('groups.invitations.revoke', { group: group.id, invitation: inv.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => successToast('Convite revogado'),
            onError: () => errorToast('Falha ao revogar'),
            onFinish: () => (actingOn.value = null),
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
                            class="rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
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

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-3 rounded border p-4">
                    <h2 class="text-sm font-semibold">Participantes ({{ group.participant_count }})</h2>
                    <ul v-if="group.participants.length" class="space-y-1 text-sm">
                        <li v-for="p in group.participants" :key="p.id" class="flex items-center gap-2 rounded bg-accent/40 px-2 py-1 text-[11px]">
                            <span class="inline-block h-5 w-5 shrink-0 rounded-full bg-primary/20 text-center text-[10px] font-medium leading-5">{{
                                p.name[0]?.toUpperCase()
                            }}</span>
                            <span class="truncate">{{ p.name }}</span>
                            <span
                                v-if="group.is_owner && p.id === userId"
                                class="rounded bg-primary/10 px-1 py-0.5 text-[9px] uppercase tracking-wide"
                                >Você</span
                            >
                            <span v-else-if="p.id === userId" class="rounded bg-primary/10 px-1 py-0.5 text-[9px] uppercase tracking-wide">Você</span>
                            <span v-if="p.id === group.id /* impossible id overlap but keep placeholder */"></span>
                        </li>
                    </ul>
                    <p v-else class="text-[11px] text-muted-foreground">Nenhum participante ainda.</p>
                </div>
                <div v-if="group.is_owner" class="space-y-3 rounded border p-4">
                    <h2 class="text-sm font-semibold">Convites</h2>
                    <ul v-if="group.invitations && group.invitations.length" class="space-y-1 text-sm">
                        <li
                            v-for="inv in group.invitations"
                            :key="inv.id"
                            class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-[11px]"
                        >
                            <div class="flex min-w-0 flex-1 flex-col">
                                <span class="truncate font-medium" :title="inv.email">{{ inv.email }}</span>
                                <span class="mt-0.5 inline-flex items-center gap-1 text-[10px]">
                                    <span
                                        :class="{
                                            'text-green-600': inv.status === 'accepted',
                                            'text-yellow-600': inv.status === 'pending',
                                            'text-destructive': inv.status === 'declined' || inv.status === 'revoked',
                                            'text-muted-foreground': inv.status === 'expired',
                                        }"
                                        class="font-medium"
                                        >{{ inv.status }}</span
                                    >
                                    <span
                                        v-if="inv.expires_at && inv.status === 'pending'"
                                        class="text-[9px] text-muted-foreground"
                                        :title="inv.expires_at"
                                    >
                                        expira em {{ new Date(inv.expires_at).toLocaleDateString() }}
                                    </span>
                                </span>
                            </div>
                            <div class="flex items-center gap-1" v-if="inv.status === 'pending'">
                                <button
                                    @click="resend(inv)"
                                    :disabled="actingOn === inv.id"
                                    class="rounded bg-accent px-2 py-0.5 text-[10px] hover:bg-accent/70 disabled:opacity-50"
                                >
                                    Reenviar
                                </button>
                                <button
                                    @click="revoke(inv)"
                                    :disabled="actingOn === inv.id"
                                    class="rounded bg-destructive px-2 py-0.5 text-[10px] text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                                >
                                    Revogar
                                </button>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-[11px] text-muted-foreground">Nenhum convite enviado.</p>
                </div>
            </div>

            <div v-if="group.is_owner && group.metrics" class="grid gap-4 rounded border p-4 text-[11px] md:grid-cols-4">
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold">Pendentes</span>
                    <span class="text-lg font-bold">{{ group.metrics.pending }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold">Aceitos</span>
                    <span class="text-lg font-bold text-green-600">{{ group.metrics.accepted }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold">Recusados</span>
                    <span class="text-lg font-bold text-destructive">{{ group.metrics.declined }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold">Revogados</span>
                    <span class="text-lg font-bold text-destructive">{{ group.metrics.revoked }}</span>
                </div>
            </div>

            <div class="text-[10px] text-muted-foreground">Após o sorteio, cada participante vê apenas seu destinatário e a wishlist associada.</div>
        </div>
    </AppLayout>
</template>

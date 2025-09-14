<script setup lang="ts">
import GroupHeaderEditable from '@/components/groups/GroupHeaderEditable.vue';
import InviteLinkPanel from '@/components/groups/InviteLinkPanel.vue';
import MetricsPanel from '@/components/groups/MetricsPanel.vue';
import RecipientPanel from '@/components/groups/RecipientPanel.vue';
import InfoTooltipLabel from '@/components/InfoTooltipLabel.vue';
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
import { Recipient, GroupShowProps as ShowProps, WishlistItem } from '@/interfaces/group';
import AppLayout from '@/layouts/AppLayout.vue';
import { useDateFormat } from '@/lib/formatDate';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Copy, Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

// Interfaces moved to '@/interfaces/group'

const props = defineProps<ShowProps>();
const { t } = useI18n();
const { formatDate } = useDateFormat();
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

const cap = (v: string) => (v ? v.charAt(0).toUpperCase() + v.slice(1) : v);

const statusBadgeClass = (status: string) => {
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
            return 'bg-muted text-muted-foreground';
    }
};

const jrSearch = ref('');
const inviteSearch = ref('');
let inviteSearchTimeout: any = null;
let jrSearchTimeout: any = null;
const invitationsLocal = ref(false); // placeholder for future local filtering toggle
const joinRequestsLocal = ref(false); // placeholder
const filteredInvitations = computed(() => {
    if (!inviteSearch.value.trim()) return group.value.invitations || [];
    const q = inviteSearch.value.toLowerCase();
    return (group.value.invitations || []).filter((inv: any) => (inv.email || '').toLowerCase().includes(q));
});
const filteredJoinRequests = computed(() => {
    if (!jrSearch.value.trim()) return group.value.join_requests || [];
    const q = jrSearch.value.toLowerCase();
    return (group.value.join_requests || []).filter(
        (jr: any) => (jr.user?.name || '').toLowerCase().includes(q) || (jr.user?.email || '').toLowerCase().includes(q),
    );
});

const updateQuery = (params: Record<string, any>) => {
    router.get(route('groups.show', group.value.id), params, {
        only: ['group'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const onInvitePage = (page: number) => {
    updateQuery({
        invite_page: page,
        invite_search: inviteSearch.value,
        jr_page: group.value.join_requests_meta?.current_page || 1,
        jr_search: jrSearch.value,
    });
};
const onJrPage = (page: number) => {
    updateQuery({
        invite_page: group.value.invitations_meta?.current_page || 1,
        invite_search: inviteSearch.value,
        jr_page: page,
        jr_search: jrSearch.value,
    });
};

// Debounced search for invitations
watch(inviteSearch, () => {
    if (invitationsLocal.value) return; // local filtering only
    clearTimeout(inviteSearchTimeout);
    inviteSearchTimeout = setTimeout(() => {
        onInvitePage(1);
    }, 300);
});
// Debounced search for join requests
watch(jrSearch, () => {
    if (joinRequestsLocal.value) return; // local filtering only
    clearTimeout(jrSearchTimeout);
    jrSearchTimeout = setTimeout(() => {
        onJrPage(1);
    }, 300);
});

const copyJoinCode = () => {
    if (!group.value.join_code) return;
    navigator.clipboard.writeText(group.value.join_code).then(() => {
        joinCodeCopied.value = true;
        setTimeout(() => (joinCodeCopied.value = false), 1600);
    });
};

const approveJoin = (id: number) => {
    joinRequestActing.value = { id, action: 'approve' };
    router.post(
        route('groups.join_requests.approve', { group: group.value.id, joinRequest: id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => (joinRequestActing.value = { id: null, action: null }),
        },
    );
};
const denyJoin = (id: number) => {
    joinRequestActing.value = { id, action: 'deny' };
    router.post(
        route('groups.join_requests.deny', { group: group.value.id, joinRequest: id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => (joinRequestActing.value = { id: null, action: null }),
        },
    );
};
const recipient = ref<Recipient | null>(null);
const recipientWishlist = ref<WishlistItem[]>([]);
const loadingRecipient = ref(false);
const drawing = ref(false);
const actingOn = ref<number | null>(null);
const joinRequestActing = ref<{ id: number | null; action: 'approve' | 'deny' | null }>({ id: null, action: null });
const joinCodeRegenerating = ref(false);
const joinCodeVisible = ref(false);
const joinCodeCopied = ref(false);
const removingParticipant = ref<number | null>(null);
const removeDialogOpen = ref(false); // NOTE: used directly in template; keep as ref for reactivity
const removeTarget = ref<any>(null);
// Ownership transfer state
const ownershipDialogOpen = ref(false);
const ownershipTarget = ref<any>(null);
const transferringOwnership = ref(false);
// Current authenticated user (from Inertia shared props)
const page = usePage();
const userId = computed(() => (page.props as any).auth?.user?.id || (page.props as any).user?.id || null);
const isParticipant = computed(() => {
    if (!userId.value) return false;
    return (group.value.participants || []).some((p: any) => p.id === userId.value);
});
const dialogMode = ref<'resend' | 'revoke' | null>(null);
const dialogInvitationId = ref<number | null>(null);
const dialogOpen = ref(false);

const openDialog = (mode: 'resend' | 'revoke', invId: number) => {
    dialogMode.value = mode;
    dialogInvitationId.value = invId;
    dialogOpen.value = true;
};

const closeDialog = () => {
    dialogOpen.value = false;
    // small timeout to allow close animation before clearing
    setTimeout(() => {
        dialogMode.value = null;
        dialogInvitationId.value = null;
    }, 150);
};

const performDialogAction = () => {
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
            onFinish: () => {
                actingOn.value = null;
                closeDialog();
            },
        },
    );
};

const fetchRecipient = () => {
    if (!props.group.has_draw) return;
    loadingRecipient.value = true;
    fetch(`/groups/${props.group.id}/recipient`, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((data) => {
            recipient.value = data.data?.user || null;
            recipientWishlist.value = data.data?.wishlist || [];
        })
        .finally(() => (loadingRecipient.value = false));
};

const runDraw = () => {
    drawing.value = true;
    router.post(
        route('groups.draw.run', props.group.id),
        {},
        {
            onSuccess: () => {
                fetchRecipient();
            },
            onFinish: () => {
                drawing.value = false;
            },
        },
    );
};

const regenerateJoinCode = () => {
    if (joinCodeRegenerating.value) return;
    joinCodeRegenerating.value = true;
    router.post(
        route('groups.regenerate_code', group.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => (joinCodeRegenerating.value = false),
        },
    );
};

const openRemoveParticipant = (p: any) => {
    removeTarget.value = p;
    removeDialogOpen.value = true;
};

const openTransferOwnership = (p: any) => {
    ownershipTarget.value = p;
    ownershipDialogOpen.value = true;
};

const confirmTransferOwnership = () => {
    if (!ownershipTarget.value) return;
    transferringOwnership.value = true;
    router.post(
        route('groups.transfer_ownership', group.value.id),
        { user_id: ownershipTarget.value.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('groups.show', group.value.id), {
                    only: ['group'],
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                });
            },
            onFinish: () => {
                transferringOwnership.value = false;
                ownershipDialogOpen.value = false;
                ownershipTarget.value = null;
            },
        },
    );
};

const confirmRemoveParticipant = () => {
    if (!removeTarget.value) return;
    removingParticipant.value = removeTarget.value.id;
    router.delete(route('groups.participants.remove', { group: group.value.id, user: removeTarget.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route('groups.show', group.value.id), {
                only: ['group'],
                preserveScroll: true,
                preserveState: true,
                replace: true,
            });
        },
        onFinish: () => {
            removingParticipant.value = null;
            removeDialogOpen.value = false;
            removeTarget.value = null;
        },
    });
};

const exclusionForm = ref<{ user_id: number | null; excluded_user_id: number | null }>({ user_id: null, excluded_user_id: null });
const exclusionSubmitting = ref(false);
const exclusionError = ref('');

function submitExclusion() {
    exclusionError.value = '';
    if (!exclusionForm.value.user_id || !exclusionForm.value.excluded_user_id) return;
    if (exclusionForm.value.user_id === exclusionForm.value.excluded_user_id) {
        exclusionError.value = t('groups.exclusion_same') || 'Participante não pode se excluir.';
        return;
    }
    exclusionSubmitting.value = true;
    router.post(
        route('groups.exclusions.store', { group: group.value.id }),
        { user_id: exclusionForm.value.user_id, excluded_user_id: exclusionForm.value.excluded_user_id },
        {
            only: ['group'],
            preserveScroll: true,
            onError: (errs: any) => {
                exclusionError.value = errs?.user_id || errs?.excluded_user_id || t('groups.error_generic') || 'Erro.';
            },
            onSuccess: () => {
                exclusionForm.value.user_id = null;
                exclusionForm.value.excluded_user_id = null;
            },
            onFinish: () => {
                exclusionSubmitting.value = false;
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
            { title: t('groups.breadcrumb_groups') || 'Grupos', href: route('groups.index') },
            { title: group.name, href: '' },
        ]"
    >
        <div class="flex flex-col gap-6 p-4">
            <GroupHeaderEditable
                :group-id="group.id"
                :name="group.name"
                :description="group.description"
                :min-gift-cents="group.min_gift_cents"
                :max-gift-cents="group.max_gift_cents"
                :currency="group.currency"
                :is-owner="group.is_owner"
            />
            <div>
                <button
                    v-if="isParticipant"
                    type="button"
                    class="mt-2 inline-flex items-center gap-1 rounded border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                    @click="router.visit(route('groups.wishlist.index', { group: group.id }))"
                >
                    🎁 {{ t('groups.my_wishlist') }}
                </button>
            </div>

            <div class="space-y-4 rounded border p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold">{{ t('groups.draw') }}</h2>
                    <div class="ml-auto flex items-center gap-2">
                        <!-- Wishlist button already present in header; removed duplicate here -->
                        <div v-if="group.is_owner && !group.has_draw">
                            <button
                                @click="runDraw"
                                :disabled="drawing || !group.can_draw"
                                class="flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                <LoaderCircle v-if="drawing" class="h-4 w-4 animate-spin" />
                                {{
                                    drawing
                                        ? t('groups.drawing') || 'Sorteando...'
                                        : group.can_draw
                                          ? t('groups.run_draw') || 'Executar Sorteio'
                                          : t('groups.waiting_participants') || 'Aguardando Participantes'
                                }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-muted-foreground">
                    <span class="inline-flex items-center gap-1 rounded bg-accent/50 px-2 py-0.5">{{
                        (t('groups.participants') || 'Participantes') + ': ' + group.participant_count
                    }}</span>
                    <span v-if="!group.has_draw && group.participant_count < 2" class="text-destructive">{{
                        t('groups.min_participants_hint') || 'Mínimo 2 participantes para sortear.'
                    }}</span>
                    <span v-if="group.has_draw" class="text-green-600 dark:text-green-400">{{
                        t('groups.draw_complete') || 'Sorteio concluído'
                    }}</span>
                </div>
                <RecipientPanel :recipient="recipient" :wishlist="recipientWishlist" :loading="loadingRecipient" :has-draw="group.has_draw" />
            </div>

            <InviteLinkPanel :group-id="group.id" :is-owner="group.is_owner" />

            <MetricsPanel :metrics="group.metrics" :is-owner="group.is_owner" />

            <!-- Exclusions (initial simple list) -->
            <div v-if="group.is_owner && group.exclusions" class="space-y-2 rounded border p-4">
                <h2 class="flex items-center gap-2 text-sm font-semibold">
                    {{ t('groups.exclusions') || 'Exclusões' }}
                    <span class="text-[10px] font-normal text-muted-foreground">beta</span>
                </h2>
                <p v-if="!group.exclusions.length" class="text-xs text-muted-foreground">
                    {{ t('groups.no_exclusions') || 'Nenhuma exclusão definida.' }}
                </p>
                <ul v-else class="space-y-1 text-xs">
                    <li v-for="ex in group.exclusions" :key="ex.id" class="flex items-center justify-between rounded bg-accent/40 px-2 py-1">
                        <span class="truncate">{{ ex.user.name }} → {{ ex.excluded_user.name }}</span>
                        <form :action="route('groups.exclusions.destroy', { group: group.id, exclusion: ex.id })" method="post" class="ml-2">
                            <input type="hidden" name="_method" value="DELETE" />
                            <button
                                class="rounded bg-destructive px-2 py-0.5 text-[10px] text-destructive-foreground hover:bg-destructive/90"
                                type="submit"
                            >
                                {{ t('groups.remove') || 'Remover' }}
                            </button>
                        </form>
                    </li>
                </ul>
                <form
                    v-if="group.participants && group.participants.length > 1"
                    @submit.prevent="submitExclusion"
                    class="mt-3 flex flex-col gap-2 rounded border-t pt-3 md:flex-row md:items-end"
                >
                    <div class="flex flex-1 flex-col gap-1">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{
                            t('groups.exclusion_giver') || 'Quem não pode tirar'
                        }}</label>
                        <select v-model="exclusionForm.user_id" class="w-full rounded border bg-background px-2 py-1 text-sm">
                            <option :value="null">-</option>
                            <option
                                v-for="p in group.participants"
                                :key="p.id"
                                :value="p.id"
                                :disabled="p.id === group.owner_id && group.participants.length === 2"
                            >
                                {{ p.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-1 flex-col gap-1">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{
                            t('groups.exclusion_receiver') || 'Não pode receber'
                        }}</label>
                        <select v-model="exclusionForm.excluded_user_id" class="w-full rounded border bg-background px-2 py-1 text-sm">
                            <option :value="null">-</option>
                            <option v-for="p in group.participants" :key="p.id" :value="p.id" :disabled="p.id === exclusionForm.user_id">
                                {{ p.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:pb-1">
                        <button
                            type="submit"
                            :disabled="!exclusionForm.user_id || !exclusionForm.excluded_user_id || exclusionSubmitting"
                            class="rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground shadow disabled:opacity-50"
                        >
                            {{ exclusionSubmitting ? t('groups.saving') || 'Salvando...' : t('groups.add_exclusion') || 'Adicionar' }}
                        </button>
                    </div>
                </form>
                <p v-if="exclusionError" class="text-xs text-destructive">{{ exclusionError }}</p>
            </div>

            <!-- Tabs navigation -->
            <div class="flex gap-2 overflow-x-auto border-b pb-2 text-sm">
                <button
                    :class="['rounded px-3 py-1', activeTab === 'participants' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
                    @click="activeTab = 'participants'"
                >
                    {{ t('groups.participants') }}
                </button>
                <button
                    v-if="group.is_owner"
                    :class="['rounded px-3 py-1', activeTab === 'invitations' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
                    @click="activeTab = 'invitations'"
                >
                    {{ t('groups.invitations') }}
                </button>
                <button
                    v-if="group.is_owner"
                    :class="['rounded px-3 py-1', activeTab === 'join_requests' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
                    @click="activeTab = 'join_requests'"
                >
                    {{ t('groups.join_requests') }}
                    <span v-if="group.pending_join_requests_count" class="ml-1 rounded bg-destructive px-1 text-xs text-destructive-foreground">{{
                        group.pending_join_requests_count
                    }}</span>
                </button>
                <div v-if="group.is_owner" class="ml-auto flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:gap-4">
                    <InfoTooltipLabel
                        :label="t('groups.entry_code')"
                        :tooltip="'Compartilhe este código com pessoas que você quer que solicitem entrada. Você pode regenerar a qualquer momento — o antigo deixa de funcionar.'"
                    />
                    <div class="flex items-center gap-3">
                        <div v-if="group.join_code" class="relative">
                            <span class="inline-flex select-text items-center gap-2 rounded bg-accent px-3 py-1 font-mono text-sm tracking-wide">
                                <span>{{ joinCodeVisible ? group.join_code : '••••••••••••' }}</span>
                                <button
                                    type="button"
                                    class="opacity-70 transition hover:opacity-100"
                                    @click="joinCodeVisible = !joinCodeVisible"
                                    :aria-label="joinCodeVisible ? 'Ocultar código' : 'Mostrar código'"
                                    :title="joinCodeVisible ? 'Ocultar código' : 'Mostrar código'"
                                >
                                    <EyeOff v-if="joinCodeVisible" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="opacity-70 transition hover:opacity-100"
                                    @click="copyJoinCode"
                                    :aria-label="joinCodeCopied ? 'Copiado!' : 'Copiar código'"
                                    :title="joinCodeCopied ? 'Copiado!' : 'Copiar código'"
                                >
                                    <Check v-if="joinCodeCopied" class="h-4 w-4 text-green-600 transition" />
                                    <Copy v-else class="h-4 w-4" />
                                </button>
                            </span>
                        </div>
                        <button
                            class="flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            :disabled="joinCodeRegenerating"
                            @click="regenerateJoinCode"
                        >
                            <LoaderCircle v-if="joinCodeRegenerating" class="h-4 w-4 animate-spin" />
                            {{ group.join_code ? t('groups.new_code') : t('groups.generate_code') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Participants Tab -->
            <div v-show="activeTab === 'participants'" class="space-y-3 rounded border p-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base font-semibold">{{ t('groups.participants') }} ({{ group.participant_count }})</h2>
                    <input
                        v-model="participantSearch"
                        :placeholder="t('groups.search')"
                        class="h-8 w-48 rounded border bg-background px-2 text-sm placeholder:text-muted-foreground"
                    />
                </div>
                <ul v-if="filteredParticipants.length" class="space-y-1 text-base">
                    <li
                        v-for="p in filteredParticipants"
                        :key="p.id"
                        class="flex items-center justify-between gap-2 rounded bg-accent/40 px-2 py-1 text-sm"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-block h-5 w-5 shrink-0 rounded-full bg-primary/20 text-center text-xs font-medium leading-5">{{
                                p.name[0]?.toUpperCase()
                            }}</span>
                            <span class="flex items-center gap-1 truncate"
                                >{{ p.name }}
                                <span
                                    v-if="p.id === group.owner_id"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[13px]"
                                    title="Dono"
                                    aria-label="Dono"
                                    >🎅</span
                                >
                            </span>
                            <span
                                v-if="p.wishlist_count"
                                class="rounded bg-amber-500/20 px-1 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                                >🎁 {{ p.wishlist_count }}</span
                            >
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <Badge v-if="p.accepted_at" class="bg-green-600 text-white hover:bg-green-600/90">
                                {{ t('groups.accepted_at') + ' ' + new Date(p.accepted_at).toLocaleString() }}
                            </Badge>
                            <div v-if="group.is_owner && p.id !== group.owner_id" class="flex items-center gap-1">
                                <button
                                    v-if="p.id !== userId && group.participant_count > 2"
                                    @click="openRemoveParticipant(p)"
                                    class="rounded bg-destructive/80 px-2 py-0.5 text-[11px] text-destructive-foreground hover:bg-destructive focus:outline-none disabled:opacity-50"
                                >
                                    {{ t('groups.remove') }}
                                </button>
                                <button
                                    @click="openTransferOwnership(p)"
                                    :disabled="transferringOwnership"
                                    class="flex items-center gap-1 rounded bg-blue-600 px-2 py-0.5 text-[11px] text-white hover:bg-blue-600/90 focus:outline-none disabled:opacity-50"
                                >
                                    <LoaderCircle v-if="transferringOwnership && ownershipTarget?.id === p.id" class="h-3 w-3 animate-spin" />
                                    <span>{{ t('groups.transfer') }}</span>
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">{{ t('groups.no_participants') }}</p>
            </div>

            <!-- Invitations Tab -->
            <div v-if="group.is_owner" v-show="activeTab === 'invitations'" class="space-y-3 rounded border p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold">{{ t('groups.invitations') }}</h2>
                    <input
                        v-model="inviteSearch"
                        :placeholder="t('groups.search_email')"
                        class="h-8 w-56 rounded border bg-background px-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>
                <ul
                    v-if="
                        (invitationsLocal ? filteredInvitations : group.invitations) &&
                        (invitationsLocal ? filteredInvitations.length : group.invitations.length)
                    "
                    class="space-y-1 text-sm"
                >
                    <li
                        v-for="inv in invitationsLocal ? filteredInvitations : group.invitations"
                        :key="inv.id"
                        class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-sm"
                    >
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-medium" :title="inv.email">{{ inv.email }}</span>
                            <span class="mt-0.5 inline-flex flex-wrap items-center gap-2 text-xs">
                                <Badge :class="statusBadgeClass(inv.status)">{{ cap(inv.status) }}</Badge>
                                <span v-if="inv.expires_at && inv.status === 'pending'" class="text-xs text-muted-foreground" :title="inv.expires_at">
                                    {{ (t('groups.expires_at') || 'expira em') + ' ' + formatDate(inv.expires_at) }}
                                </span>
                                <span v-if="inv.created_at" class="text-xs text-muted-foreground">{{
                                    (t('groups.sent') || 'enviado') + ' ' + formatDate(inv.created_at)
                                }}</span>
                                <span v-if="inv.accepted_at" class="text-xs text-green-600"
                                    >{{ t('groups.accepted') }} {{ formatDate(inv.accepted_at) }}</span
                                >
                                <span v-if="inv.declined_at" class="text-xs text-destructive">Recusado {{ formatDate(inv.declined_at) }}</span>
                                <span v-if="inv.revoked_at" class="text-[11px] text-destructive">Revogado {{ formatDate(inv.revoked_at) }}</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-1" v-if="inv.status === 'pending' || inv.status === 'revoked'">
                            <button
                                @click.prevent="openDialog('resend', inv.id)"
                                :disabled="actingOn === inv.id"
                                class="rounded bg-accent px-2 py-0.5 text-xs hover:bg-accent/70 disabled:opacity-50"
                            >
                                {{ t('groups.resend') || 'Reenviar' }}
                            </button>
                            <button
                                @click.prevent="openDialog('revoke', inv.id)"
                                :disabled="actingOn === inv.id"
                                class="rounded bg-destructive px-2 py-0.5 text-xs text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                            >
                                {{ t('groups.revoke') || 'Revogar' }}
                            </button>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">{{ t('groups.no_invites') }}</p>
                <p v-if="invitationsLocal && inviteSearch && !filteredInvitations.length" class="text-xs text-muted-foreground">
                    {{ t('groups.no_results').replace(':query', inviteSearch) }}
                </p>
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
                    <h2 class="text-base font-semibold">{{ t('groups.join_requests') }}</h2>
                    <input
                        v-model="jrSearch"
                        :placeholder="t('groups.search_user')"
                        class="h-8 w-56 rounded border bg-background px-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>
                <ul
                    v-if="
                        (joinRequestsLocal ? filteredJoinRequests : group.join_requests) &&
                        (joinRequestsLocal ? filteredJoinRequests.length : group.join_requests.length)
                    "
                    class="space-y-1 text-sm"
                >
                    <li
                        v-for="jr in joinRequestsLocal ? filteredJoinRequests : group.join_requests"
                        :key="jr.id"
                        class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-sm"
                    >
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-medium" :title="jr.user?.email">{{ jr.user?.name || t('groups.user') || 'Usuário' }}</span>
                            <span class="text-xs text-muted-foreground">{{ jr.user?.email }}</span>
                        </div>
                        <div class="flex items-center gap-1" v-if="jr.status === 'pending'">
                            <button
                                @click.prevent="approveJoin(jr.id)"
                                :disabled="joinRequestActing.id === jr.id"
                                class="flex items-center gap-1 rounded bg-green-600 px-2 py-0.5 text-xs text-white hover:bg-green-700 disabled:opacity-50"
                            >
                                <LoaderCircle
                                    v-if="joinRequestActing.id === jr.id && joinRequestActing.action === 'approve'"
                                    class="h-3 w-3 animate-spin"
                                />
                                {{ t('groups.approve') || 'Aceitar' }}
                            </button>
                            <button
                                @click.prevent="denyJoin(jr.id)"
                                :disabled="joinRequestActing.id === jr.id"
                                class="flex items-center gap-1 rounded bg-destructive px-2 py-0.5 text-xs text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                            >
                                <LoaderCircle
                                    v-if="joinRequestActing.id === jr.id && joinRequestActing.action === 'deny'"
                                    class="h-3 w-3 animate-spin"
                                />
                                {{ t('groups.deny') || 'Recusar' }}
                            </button>
                        </div>
                        <span v-else class="inline-flex flex-wrap items-center gap-2">
                            <Badge :class="statusBadgeClass(jr.status)">{{ cap(jr.status) }}</Badge>
                            <span class="text-xs text-muted-foreground" v-if="jr.created_at">{{
                                (t('groups.sent') || 'Enviado') + ' ' + formatDate(jr.created_at)
                            }}</span>
                            <span class="text-xs text-green-600" v-if="jr.approved_at">{{
                                (t('groups.approved') || 'Aprovado') + ' ' + formatDate(jr.approved_at)
                            }}</span>
                            <span class="text-xs text-destructive" v-if="jr.denied_at">{{
                                (t('groups.denied') || 'Recusado') + ' ' + formatDate(jr.denied_at)
                            }}</span>
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">{{ t('groups.no_join_requests') }}</p>
                <p v-if="joinRequestsLocal && jrSearch && !filteredJoinRequests.length" class="text-xs text-muted-foreground">
                    {{ t('groups.no_results').replace(':query', jrSearch) }}
                </p>
                <div v-if="group.join_requests_meta && group.join_requests_meta.last_page > 1" class="pt-2">
                    <Pagination
                        :page="group.join_requests_meta.current_page"
                        :items-per-page="group.join_requests_meta.per_page"
                        :total="group.join_requests_meta.total"
                        @update:page="onJrPage"
                    />
                </div>
            </div>

            <p class="mt-4 text-sm text-muted-foreground">
                {{ t('groups.post_draw_hint') || 'Após o sorteio, cada participante vê apenas seu destinatário e a wishlist associada.' }}
            </p>
            <div v-if="group.is_owner && group.activities && group.activities.length" class="space-y-2 rounded border p-4">
                <h2 class="text-sm font-semibold">{{ t('groups.activities') }}</h2>
                <ul class="space-y-1 text-xs">
                    <li v-for="a in group.activities" :key="a.id" class="flex items-center justify-between">
                        <span class="font-mono">{{ a.action }}</span>
                        <span class="text-muted-foreground">{{ new Date(a.created_at).toLocaleString() }}</span>
                    </li>
                </ul>
            </div>
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
                        {{
                            dialogMode === 'revoke'
                                ? t('groups.revoke_invite_title') || 'Revogar convite?'
                                : t('groups.resend_invite_title') || 'Reenviar convite?'
                        }}
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-xs">
                        <template v-if="dialogMode === 'revoke'">
                            {{
                                t('groups.revoke_invite_desc') ||
                                'Essa ação impedirá que o convidado aceite o convite existente. Você poderá criar outro depois.'
                            }}
                        </template>
                        <template v-else>
                            {{
                                t('groups.resend_invite_desc') ||
                                'Um novo token será gerado e o anterior se torna inválido. Garanta que vai reenviar o link atualizado por e-mail.'
                            }}
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeDialog" class="text-xs">{{ t('groups.cancel') }}</AlertDialogCancel>
                    <AlertDialogAction @click="performDialogAction" :disabled="actingOn !== null" class="flex items-center gap-2 text-xs">
                        <LoaderCircle v-if="actingOn !== null" class="h-4 w-4 animate-spin" />
                        {{ dialogMode === 'revoke' ? t('groups.revoke') || 'Revogar' : t('groups.resend') || 'Reenviar' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
        <!-- Remove Participant Dialog -->
        <AlertDialog
            :open="removeDialogOpen"
            @update:open="
                (v: any) => {
                    if (!v) {
                        removeDialogOpen = false;
                        removeTarget.value = null;
                    }
                }
            "
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ t('groups.confirm_remove_title') }}</AlertDialogTitle>
                    <AlertDialogDescription class="text-sm">
                        {{ t('groups.confirm_remove_desc') }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="removingParticipant !== null">{{ t('groups.cancel') }}</AlertDialogCancel>
                    <AlertDialogAction
                        @click="confirmRemoveParticipant"
                        :disabled="removingParticipant !== null"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        <LoaderCircle v-if="removingParticipant !== null" class="h-4 w-4 animate-spin" />
                        {{ t('groups.confirm') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
        <!-- Transfer Ownership Dialog -->
        <AlertDialog
            :open="ownershipDialogOpen"
            @update:open="
                (v: any) => {
                    if (!v) {
                        ownershipDialogOpen = false;
                        ownershipTarget.value = null;
                    }
                }
            "
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ t('groups.confirm_transfer_title') }}</AlertDialogTitle>
                    <AlertDialogDescription class="text-sm">
                        {{ t('groups.confirm_transfer_desc') }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="transferringOwnership">{{ t('groups.cancel') }}</AlertDialogCancel>
                    <AlertDialogAction
                        @click="confirmTransferOwnership"
                        :disabled="transferringOwnership"
                        class="bg-blue-600 text-white hover:bg-blue-600/90"
                    >
                        <LoaderCircle v-if="transferringOwnership" class="h-4 w-4 animate-spin" />
                        {{ t('groups.confirm') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>

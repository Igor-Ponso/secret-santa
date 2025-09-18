<script setup lang="ts">
import GroupActivitiesPanel from '@/components/groups/GroupActivitiesPanel.vue';
import GroupDrawPanel from '@/components/groups/GroupDrawPanel.vue';
import GroupExclusionsPanel from '@/components/groups/GroupExclusionsPanel.vue';
import GroupHeaderEditable from '@/components/groups/GroupHeaderEditable.vue';
import GroupTabsNav from '@/components/groups/GroupTabsNav.vue';
import InvitationsList from '@/components/groups/InvitationsList.vue';
import InviteLinkPanel from '@/components/groups/InviteLinkPanel.vue';
import JoinRequestsList from '@/components/groups/JoinRequestsList.vue';
import MetricsPanel from '@/components/groups/MetricsPanel.vue';
import ParticipantsList from '@/components/groups/ParticipantsList.vue';
import InvitationActionDialog from '@/components/groups/dialogs/InvitationActionDialog.vue';
import RemoveParticipantDialog from '@/components/groups/dialogs/RemoveParticipantDialog.vue';
import TransferOwnershipDialog from '@/components/groups/dialogs/TransferOwnershipDialog.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import { Recipient, GroupShowProps as ShowProps, WishlistItem } from '@/interfaces/group';
import AppLayout from '@/layouts/AppLayout.vue';
import { useDateFormat } from '@/lib/formatDate';
import { Head, router, usePage } from '@inertiajs/vue3';
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
// Local participant client-side search (backend now guarantees no duplicate/owner phantom invites)
const participantSearch = ref('');
const filteredParticipants = computed(() => {
    if (!participantSearch.value.trim()) return group.value.participants || [];
    const q = participantSearch.value.toLowerCase();
    return (group.value.participants || []).filter((p: any) => p.name?.toLowerCase().includes(q));
});

const jrSearch = ref('');
const inviteSearch = ref('');
let inviteSearchTimeout: any = null;
let jrSearchTimeout: any = null;
// Invitations simple filter (backend now blocks owner/self + duplicates)
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

// join code copy moved into GroupJoinCodePanel via emitted event

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
    router.post(route('groups.regenerate_code', group.value.id), {}, { preserveScroll: true, onFinish: () => (joinCodeRegenerating.value = false) });
};

function markJoinCodeCopied() {
    joinCodeCopied.value = true;
    setTimeout(() => (joinCodeCopied.value = false), 1600);
}

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
                    preserveState: false,
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
                preserveState: false,
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

// exclusions logic moved into GroupExclusionsPanel (emit driven)

const handleExclusionCreate = (payload: { user_id: number; excluded_user_id: number; onDone: () => void; onError: (msg: string) => void }) => {
    router.post(
        route('groups.exclusions.store', { group: group.value.id }),
        { user_id: payload.user_id, excluded_user_id: payload.excluded_user_id },
        {
            only: ['group'],
            preserveScroll: true,
            onError: (errs: any) => {
                payload.onError(errs?.user_id || errs?.excluded_user_id || (t('groups.error_generic') as string) || 'Erro.');
            },
            onSuccess: () => payload.onDone(),
        },
    );
};
const handleExclusionDelete = (exclusionId: number) => {
    router.delete(route('groups.exclusions.destroy', { group: group.value.id, exclusion: exclusionId }), { preserveScroll: true, only: ['group'] });
};

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

            <GroupDrawPanel
                :group="group"
                :drawing="drawing"
                :recipient="recipient"
                :recipient-wishlist="recipientWishlist"
                :loading-recipient="loadingRecipient"
                @run-draw="runDraw"
            />

            <InviteLinkPanel :group-id="group.id" :is-owner="group.is_owner" />

            <MetricsPanel :metrics="group.metrics" :is-owner="group.is_owner" />

            <GroupExclusionsPanel v-if="group.is_owner" :group="group" @create="handleExclusionCreate" @delete="handleExclusionDelete" />

            <GroupTabsNav
                :group="group"
                :active-tab="activeTab"
                :join-code-visible="joinCodeVisible"
                :join-code-copied="joinCodeCopied"
                :join-code-regenerating="joinCodeRegenerating"
                @update:tab="(v: any) => (activeTab = v)"
                @toggle-join-code="() => (joinCodeVisible = !joinCodeVisible)"
                @copy-join-code="markJoinCodeCopied"
                @regenerate-code="regenerateJoinCode"
            />

            <!-- Participants Tab -->
            <ParticipantsList
                v-show="activeTab === 'participants'"
                :group="group"
                :participants="filteredParticipants"
                :participant-search="participantSearch"
                :user-id="userId"
                :removing-participant="removingParticipant"
                :transferring-ownership="transferringOwnership"
                :ownership-target-id="ownershipTarget?.id || null"
                @update:participantSearch="(v) => (participantSearch = v)"
                @remove="openRemoveParticipant"
                @transfer="openTransferOwnership"
            />

            <!-- Invitations Tab -->
            <div v-if="group.is_owner" v-show="activeTab === 'invitations'">
                <InvitationsList
                    :invitations="filteredInvitations"
                    :invite-search="inviteSearch"
                    :acting-on="actingOn"
                    :format-date="formatDate"
                    :can-paginate="!!(group.invitations_meta && group.invitations_meta.last_page > 1)"
                    @update:inviteSearch="(v) => (inviteSearch = v)"
                    @resend="(id) => openDialog('resend', id)"
                    @revoke="(id) => openDialog('revoke', id)"
                >
                    <template #pagination>
                        <div v-if="group.invitations_meta && group.invitations_meta.last_page > 1" class="pt-2">
                            <Pagination
                                :page="group.invitations_meta.current_page"
                                :items-per-page="group.invitations_meta.per_page"
                                :total="group.invitations_meta.total"
                                @update:page="onInvitePage"
                            />
                        </div>
                    </template>
                </InvitationsList>
                <p v-if="inviteSearch && !filteredInvitations.length" class="text-xs text-muted-foreground">
                    {{ t('groups.no_results').replace(':query', inviteSearch) }}
                </p>
            </div>

            <!-- Join Requests Tab -->
            <div v-if="group.is_owner" v-show="activeTab === 'join_requests'">
                <JoinRequestsList
                    :join-requests="filteredJoinRequests"
                    :jr-search="jrSearch"
                    :join-request-acting="joinRequestActing"
                    :format-date="formatDate"
                    @update:jrSearch="(v) => (jrSearch = v)"
                    @approve="approveJoin"
                    @deny="denyJoin"
                >
                    <template #pagination>
                        <div v-if="group.join_requests_meta && group.join_requests_meta.last_page > 1" class="pt-2">
                            <Pagination
                                :page="group.join_requests_meta.current_page"
                                :items-per-page="group.join_requests_meta.per_page"
                                :total="group.join_requests_meta.total"
                                @update:page="onJrPage"
                            />
                        </div>
                    </template>
                </JoinRequestsList>
                <p v-if="jrSearch && !filteredJoinRequests.length" class="text-xs text-muted-foreground">
                    {{ t('groups.no_results').replace(':query', jrSearch) }}
                </p>
            </div>

            <p class="mt-4 text-sm text-muted-foreground">
                {{ t('groups.post_draw_hint') || 'Após o sorteio, cada participante vê apenas seu destinatário e a wishlist associada.' }}
            </p>
            <GroupActivitiesPanel v-if="group.is_owner" :activities="group.activities" />
        </div>

        <InvitationActionDialog
            :mode="dialogMode"
            :open="dialogOpen"
            :acting-on="actingOn !== null"
            @close="closeDialog"
            @confirm="performDialogAction"
        />
        <RemoveParticipantDialog
            :open="removeDialogOpen"
            :busy="removingParticipant !== null"
            @close="
                () => {
                    removeDialogOpen = false;
                    removeTarget.value = null;
                }
            "
            @confirm="confirmRemoveParticipant"
        />
        <TransferOwnershipDialog
            :open="ownershipDialogOpen"
            :busy="transferringOwnership"
            @close="
                () => {
                    ownershipDialogOpen = false;
                    ownershipTarget.value = null;
                }
            "
            @confirm="confirmTransferOwnership"
        />
    </AppLayout>
</template>

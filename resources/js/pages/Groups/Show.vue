<script setup lang="ts">
import GroupActivitiesPanel from '@/components/groups/GroupActivitiesPanel.vue';
import GroupDrawPanel from '@/components/groups/GroupDrawPanel.vue';
import GroupExclusionsTab from '@/components/groups/GroupExclusionsTab.vue';
import GroupHeaderEditable from '@/components/groups/GroupHeaderEditable.vue';
import GroupTabsNav from '@/components/groups/GroupTabsNav.vue';
import InviteLinkPanel from '@/components/groups/InviteLinkPanel.vue';
import MetricsPanel from '@/components/groups/MetricsPanel.vue';
import ParticipantsList from '@/components/groups/ParticipantsList.vue';
import InvitationActionDialog from '@/components/groups/dialogs/InvitationActionDialog.vue';
import RemoveParticipantDialog from '@/components/groups/dialogs/RemoveParticipantDialog.vue';
import TransferOwnershipDialog from '@/components/groups/dialogs/TransferOwnershipDialog.vue';
import GroupInvitationsTab from '@/components/groups/tabs/GroupInvitationsTab.vue';
import GroupJoinRequestsTab from '@/components/groups/tabs/GroupJoinRequestsTab.vue';
import { Recipient, GroupShowProps as ShowProps, WishlistItem } from '@/interfaces/group';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

// Interfaces moved to '@/interfaces/group'

const props = defineProps<ShowProps>();
const { t } = useI18n();
// Computed wrapper so when Inertia replaces props the template reacts
const group = computed(() => props.group);
// Tabs (backend-sanitized)
const activeTab = ref<'participants' | 'invitations' | 'join_requests' | 'exclusions'>(props.group.initial_tab || 'participants');
// Local participant client-side search (backend now guarantees no duplicate/owner phantom invites)
const participantSearch = ref('');
const filteredParticipants = computed(() => {
    if (!participantSearch.value.trim()) return group.value.participants || [];
    const q = participantSearch.value.toLowerCase();
    return (group.value.participants || []).filter((p: any) => p.name?.toLowerCase().includes(q));
});

// Invitation & join request logic moved into dedicated tab components

// join code copy moved into GroupJoinCodePanel via emitted event

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

// Obsolete exclusion handlers removed (handled inside GroupExclusionsTab)

// Invitation form logic extracted

function setTab(tab: 'participants' | 'invitations' | 'join_requests' | 'exclusions') {
    activeTab.value = tab;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url.toString());
}

// Auto-focus invitation email input when switching to invitations tab
// Invitation field autofocus now handled in tab component (if desired)

onMounted(fetchRecipient);

// When server sends updated invitations list, remove any optimistic entries now present in real data
// Optimistic invitation reconciliation now handled inside GroupInvitationsTab
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
                :description="group.description ?? null"
                :min-gift-cents="group.min_gift_cents"
                :max-gift-cents="group.max_gift_cents"
                :currency="group.currency"
                :is-owner="group.is_owner"
                :has-draw="group.has_draw"
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

            <!-- Consolidated draw panel (status + readiness moved inside component) -->
            <GroupDrawPanel
                :group="group"
                :drawing="drawing"
                :recipient="recipient"
                :recipient-wishlist="recipientWishlist"
                :loading-recipient="loadingRecipient"
                @run-draw="runDraw"
            />

            <InviteLinkPanel :group-id="group.id" :is-owner="group.is_owner" />

            <MetricsPanel :metrics="group.metrics || null" :is-owner="group.is_owner" />
            <!-- Removed standalone GroupReadinessBadges -->

            <!-- Exclusions moved into dedicated tab -->

            <GroupTabsNav
                :group="group"
                :active-tab="activeTab"
                :join-code-visible="joinCodeVisible"
                :join-code-copied="joinCodeCopied"
                :join-code-regenerating="joinCodeRegenerating"
                @update:tab="(v: any) => setTab(v)"
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
            <GroupInvitationsTab
                v-if="group.is_owner"
                v-show="activeTab === 'invitations'"
                :group="group"
                :acting-on="actingOn"
                :on-open-dialog="openDialog"
            />

            <!-- Join Requests Tab -->
            <GroupJoinRequestsTab
                v-if="group.is_owner"
                v-show="activeTab === 'join_requests'"
                :group="group"
                :join-request-acting="joinRequestActing"
            />

            <!-- Exclusions Tab -->
            <div v-if="group.is_owner" v-show="activeTab === 'exclusions'">
                <GroupExclusionsTab :group="group" />
            </div>

            <p class="mt-4 text-sm text-muted-foreground">
                {{ t('groups.post_draw_hint') }}
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

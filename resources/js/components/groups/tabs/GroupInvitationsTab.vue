<script setup lang="ts">
import InvitationsList from '@/components/groups/InvitationsList.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import { useDateFormat } from '@/lib/formatDate';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    group: any;
    actingOn: number | null;
    onOpenDialog: (mode: 'resend' | 'revoke', id: number) => void;
}

const props = defineProps<Props>();
const { t } = useI18n();
const { formatDate } = useDateFormat();

const inviteSearch = ref('');
const inviteFormEmail = ref('');
const inviteEmailInput = ref<HTMLInputElement | null>(null);
const optimisticInvites = ref<any[]>([]);
let searchTimeout: any = null;

const invitationsCombined = computed(() => {
    const base = props.group.invitations || [];
    const optimisticFiltered = optimisticInvites.value.filter(
        (o) => !base.some((b: any) => (b.email || '').toLowerCase() === (o.email || '').toLowerCase()),
    );
    return [...optimisticFiltered, ...base];
});

const filteredInvitations = computed(() => {
    const list = invitationsCombined.value;
    if (!inviteSearch.value.trim()) return list;
    const q = inviteSearch.value.toLowerCase();
    return list.filter((inv: any) => (inv.email || '').toLowerCase().includes(q));
});

watch(inviteSearch, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => onInvitePage(1), 300);
});

watch(
    () => props.group.invitations,
    (latest) => {
        if (!latest || !optimisticInvites.value.length) return;
        const latestEmails = latest.map((i: any) => (i.email || '').toLowerCase());
        optimisticInvites.value = optimisticInvites.value.filter((o) => !latestEmails.includes((o.email || '').toLowerCase()));
    },
    { deep: true },
);

function onInvitePage(page: number) {
    router.get(
        route('groups.show', props.group.id),
        {
            tab: 'invitations',
            invite_page: page,
            invite_search: inviteSearch.value,
            jr_page: props.group.join_requests_meta?.current_page || 1,
            jr_search: '',
        },
        { only: ['group'], preserveState: true, preserveScroll: true, replace: true },
    );
}

function submitInvite() {
    if (!inviteFormEmail.value) return;
    const email = inviteFormEmail.value.trim().toLowerCase();
    if (!optimisticInvites.value.some((o) => o.email === email)) {
        optimisticInvites.value.unshift({
            id: Date.now() * -1,
            email,
            created_at: new Date().toISOString(),
            accepted_at: null,
            declined_at: null,
            revoked_at: null,
            expires_at: null,
            is_optimistic: true,
        });
    }
    router.post(
        route('groups.invitations.store', props.group.id),
        { email },
        {
            preserveScroll: true,
            onSuccess: () => {
                optimisticInvites.value = optimisticInvites.value.filter((o) => o.email !== email);
                inviteFormEmail.value = '';
                nextTick(() => inviteEmailInput.value?.focus());
            },
            onError: () => {
                optimisticInvites.value = optimisticInvites.value.filter((o) => o.email !== email);
            },
        },
    );
}

function openResend(id: number) {
    props.onOpenDialog('resend', id);
}
function openRevoke(id: number) {
    props.onOpenDialog('revoke', id);
}
</script>

<template>
    <div>
        <form @submit.prevent="submitInvite" class="mb-4 flex flex-col gap-2 rounded border bg-muted/40 p-3">
            <label class="text-xs font-medium" for="invite-email">Adicionar convite (email)</label>
            <div class="flex gap-2">
                <input
                    id="invite-email"
                    ref="inviteEmailInput"
                    v-model="inviteFormEmail"
                    type="email"
                    required
                    placeholder="email@exemplo.com"
                    class="flex-1 rounded border px-2 py-1 text-sm"
                />
                <button
                    :disabled="!inviteFormEmail"
                    type="submit"
                    class="inline-flex items-center rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
                >
                    Convidar
                </button>
            </div>
        </form>

        <InvitationsList
            :invitations="filteredInvitations"
            :invite-search="inviteSearch"
            :acting-on="props.actingOn"
            :format-date="formatDate"
            :can-paginate="!!(props.group.invitations_meta && props.group.invitations_meta.last_page > 1)"
            @update:inviteSearch="(v) => (inviteSearch = v)"
            @resend="openResend"
            @revoke="openRevoke"
        >
            <template #pagination>
                <div v-if="props.group.invitations_meta && props.group.invitations_meta.last_page > 1" class="pt-2">
                    <Pagination
                        :page="props.group.invitations_meta.current_page"
                        :items-per-page="props.group.invitations_meta.per_page"
                        :total="props.group.invitations_meta.total"
                        @update:page="onInvitePage"
                    />
                </div>
            </template>
        </InvitationsList>
        <p v-if="inviteSearch && !filteredInvitations.length" class="text-xs text-muted-foreground">
            {{ t('groups.no_results').replace(':query', inviteSearch) }}
        </p>
    </div>
</template>

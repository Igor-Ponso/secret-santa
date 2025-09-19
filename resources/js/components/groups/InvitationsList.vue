<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useI18n } from 'vue-i18n';

interface Invitation {
    id: number;
    email: string;
    status: string;
    expires_at?: string | null;
    created_at?: string | null;
    accepted_at?: string | null;
    declined_at?: string | null;
    revoked_at?: string | null;
}

interface Props {
    invitations: Invitation[];
    inviteSearch: string;
    actingOn: number | null;
    formatDate: (v: string) => string;
    canPaginate: boolean;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:inviteSearch', value: string): void;
    (e: 'resend', id: number): void;
    (e: 'revoke', id: number): void;
}>();

const { t } = useI18n();

const statusBadgeClass = (status: string) => {
    switch (status) {
        case 'accepted':
            return 'bg-green-600 text-white hover:bg-green-600/90';
        case 'pending':
            return 'bg-amber-500 text-white hover:bg-amber-500/90';
        case 'revoked':
        case 'declined':
            return 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
        case 'expired':
            return 'bg-muted text-muted-foreground';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <div class="space-y-3 rounded border p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold">{{ t('groups.invitations') }}</h2>
            <input
                :value="inviteSearch"
                @input="(e: any) => emit('update:inviteSearch', e.target.value)"
                :placeholder="t('groups.search_email')"
                class="h-8 w-56 rounded border bg-background px-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
            />
        </div>
        <ul v-if="invitations && invitations.length" class="space-y-1 text-sm">
            <li v-for="inv in invitations" :key="inv.id" class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-sm">
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate font-medium" :title="inv.email">{{ inv.email }}</span>
                    <span class="mt-0.5 inline-flex flex-wrap items-center gap-2 text-xs">
                        <Badge :class="statusBadgeClass(inv.status)">{{ inv.status }}</Badge>
                        <span v-if="inv.expires_at && inv.status === 'pending'" class="text-xs text-muted-foreground" :title="inv.expires_at">
                            {{ t('groups.expires_at') + ' ' + formatDate(inv.expires_at) }}
                        </span>
                        <span v-if="inv.created_at" class="text-xs text-muted-foreground">
                            {{ t('groups.sent') + ' ' + formatDate(inv.created_at) }}
                        </span>
                        <span v-if="inv.accepted_at" class="text-xs text-green-600"
                            >{{ t('groups.accepted') }} {{ formatDate(inv.accepted_at) }}</span
                        >
                        <span v-if="inv.declined_at" class="text-xs text-destructive">
                            {{ t('groups.declined') }} {{ formatDate(inv.declined_at) }}
                        </span>
                        <span v-if="inv.revoked_at" class="text-[11px] text-destructive">
                            {{ t('groups.revoked') }} {{ formatDate(inv.revoked_at) }}
                        </span>
                    </span>
                </div>
                <div class="flex items-center gap-1" v-if="inv.status === 'pending' || inv.status === 'revoked'">
                    <button
                        @click.prevent="emit('resend', inv.id)"
                        class="rounded bg-accent px-2 py-0.5 text-xs hover:bg-accent/70 disabled:opacity-50"
                    >
                        {{ t('groups.resend') }}
                    </button>
                    <button
                        @click.prevent="emit('revoke', inv.id)"
                        class="rounded bg-destructive px-2 py-0.5 text-xs text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                    >
                        {{ t('groups.revoke') }}
                    </button>
                </div>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">{{ t('groups.no_invites') }}</p>
        <slot name="pagination" />
    </div>
</template>

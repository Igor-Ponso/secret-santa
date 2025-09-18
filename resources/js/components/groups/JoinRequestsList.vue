<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

interface JoinRequest {
    id: number;
    status: string;
    created_at?: string | null;
    approved_at?: string | null;
    denied_at?: string | null;
    user?: { name?: string; email?: string };
}

interface Props {
    joinRequests: JoinRequest[];
    jrSearch: string;
    joinRequestActing: { id: number | null; action: 'approve' | 'deny' | null };
    formatDate: (v: string) => string;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:jrSearch', value: string): void;
    (e: 'approve', id: number): void;
    (e: 'deny', id: number): void;
}>();

const { t } = useI18n();

const statusBadgeClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-green-600 text-white hover:bg-green-600/90';
        case 'pending':
            return 'bg-amber-500 text-white hover:bg-amber-500/90';
        case 'denied':
            return 'bg-destructive text-destructive-foreground hover:bg-destructive/90';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <div class="space-y-3 rounded border p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold">{{ t('groups.join_requests') }}</h2>
            <input
                :value="jrSearch"
                @input="(e: any) => emit('update:jrSearch', e.target.value)"
                :placeholder="t('groups.search_user')"
                class="h-8 w-56 rounded border bg-background px-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
            />
        </div>
        <ul v-if="joinRequests && joinRequests.length" class="space-y-1 text-sm">
            <li v-for="jr in joinRequests" :key="jr.id" class="flex items-center justify-between gap-2 rounded border px-2 py-1 text-sm">
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate font-medium" :title="jr.user?.email">{{ jr.user?.name || t('groups.user') || 'Usuário' }}</span>
                    <span class="text-xs text-muted-foreground">{{ jr.user?.email }}</span>
                </div>
                <div class="flex items-center gap-1" v-if="jr.status === 'pending'">
                    <button
                        @click.prevent="emit('approve', jr.id)"
                        :disabled="joinRequestActing.id === jr.id"
                        class="flex items-center gap-1 rounded bg-green-600 px-2 py-0.5 text-xs text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        <LoaderCircle v-if="joinRequestActing.id === jr.id && joinRequestActing.action === 'approve'" class="h-3 w-3 animate-spin" />
                        {{ t('groups.approve') || 'Aceitar' }}
                    </button>
                    <button
                        @click.prevent="emit('deny', jr.id)"
                        :disabled="joinRequestActing.id === jr.id"
                        class="flex items-center gap-1 rounded bg-destructive px-2 py-0.5 text-xs text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                    >
                        <LoaderCircle v-if="joinRequestActing.id === jr.id && joinRequestActing.action === 'deny'" class="h-3 w-3 animate-spin" />
                        {{ t('groups.deny') || 'Recusar' }}
                    </button>
                </div>
                <span v-else class="inline-flex flex-wrap items-center gap-2">
                    <Badge :class="statusBadgeClass(jr.status)">{{ jr.status }}</Badge>
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
        <slot name="pagination" />
    </div>
</template>

<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

interface Participant {
    id: number;
    name: string;
    accepted_at?: string | null;
    wishlist_count?: number | null;
}

interface Props {
    group: any; // parent passes full group (light typing here for speed)
    participants: Participant[];
    participantSearch: string;
    userId: number | null;
    removingParticipant: number | null;
    transferringOwnership: boolean;
    ownershipTargetId: number | null;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:participantSearch', value: string): void;
    (e: 'remove', participant: Participant): void;
    (e: 'transfer', participant: Participant): void;
}>();

const { t } = useI18n();

const onSearch = (e: Event) => emit('update:participantSearch', (e.target as HTMLInputElement).value);

function initial(n: string) {
    return n?.charAt(0)?.toUpperCase() || '?';
}
</script>

<template>
    <div class="space-y-3 rounded border p-4">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold">{{ t('groups.participants') }} ({{ group.participant_count }})</h2>
            <input
                :value="participantSearch"
                @input="onSearch"
                :placeholder="t('groups.search')"
                class="h-8 w-48 rounded border bg-background px-2 text-sm placeholder:text-muted-foreground"
            />
        </div>
        <ul v-if="participants.length" class="space-y-1 text-base">
            <li v-for="p in participants" :key="p.id" class="flex items-center justify-between gap-2 rounded bg-accent/40 px-2 py-1 text-sm">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="inline-block h-5 w-5 shrink-0 rounded-full bg-primary/20 text-center text-xs font-medium leading-5">{{
                        initial(p.name)
                    }}</span>
                    <span class="flex items-center gap-1 truncate">
                        {{ p.name }}
                        <span
                            v-if="p.id === group.owner_id"
                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[13px]"
                            title="Dono"
                            aria-label="Dono"
                            >🎅</span
                        >
                    </span>
                    <span v-if="p.wishlist_count" class="rounded bg-amber-500/20 px-1 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                        >🎁 {{ p.wishlist_count }}</span
                    >
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span v-if="p.accepted_at" class="rounded bg-green-600 px-2 py-0.5 text-[11px] font-medium text-white">{{
                        t('groups.accepted_at') + ' ' + new Date(p.accepted_at).toLocaleString()
                    }}</span>
                    <div v-if="group.is_owner && p.id !== group.owner_id" class="flex items-center gap-1">
                        <button
                            v-if="p.id !== userId && group.participant_count > 2"
                            @click="emit('remove', p)"
                            class="rounded bg-destructive/80 px-2 py-0.5 text-[11px] text-destructive-foreground hover:bg-destructive focus:outline-none disabled:opacity-50"
                        >
                            {{ t('groups.remove') }}
                        </button>
                        <button
                            @click="emit('transfer', p)"
                            :disabled="transferringOwnership"
                            class="flex items-center gap-1 rounded bg-blue-600 px-2 py-0.5 text-[11px] text-white hover:bg-blue-600/90 focus:outline-none disabled:opacity-50"
                        >
                            <LoaderCircle v-if="transferringOwnership && ownershipTargetId === p.id" class="h-3 w-3 animate-spin" />
                            <span>{{ t('groups.transfer') }}</span>
                        </button>
                    </div>
                </div>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">{{ t('groups.no_participants') }}</p>
    </div>
</template>

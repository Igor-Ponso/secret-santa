<script setup lang="ts">
import RecipientPanel from '@/components/groups/RecipientPanel.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

interface Props {
    group: any;
    drawing: boolean;
    recipient: any;
    recipientWishlist: any[];
    loadingRecipient: boolean;
}

defineProps<Props>();
const emit = defineEmits<{ (e: 'run-draw'): void }>();
const { t } = useI18n();
</script>

<template>
    <div class="space-y-4 rounded border p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold">{{ t('groups.draw') }}</h2>
            <div class="ml-auto flex items-center gap-2">
                <div v-if="group.is_owner && !group.has_draw">
                    <button
                        @click="emit('run-draw')"
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
            <span class="inline-flex items-center gap-1 rounded bg-accent/50 px-2 py-0.5">
                {{ (t('groups.participants') || 'Participantes') + ': ' + group.participant_count }}
            </span>
            <span v-if="!group.has_draw && group.participant_count < 2" class="text-destructive">
                {{ t('groups.min_participants_hint') || 'Mínimo 2 participantes para sortear.' }}
            </span>
            <span v-if="group.has_draw" class="text-green-600 dark:text-green-400">
                {{ t('groups.draw_complete') || 'Sorteio concluído' }}
            </span>
        </div>
        <RecipientPanel :recipient="recipient" :wishlist="recipientWishlist" :loading="loadingRecipient" :has-draw="group.has_draw" />
    </div>
</template>

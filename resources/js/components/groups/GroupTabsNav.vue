<script setup lang="ts">
import InfoTooltipLabel from '@/components/InfoTooltipLabel.vue';
import { Check, Copy, Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

interface Props {
    group: any;
    activeTab: 'participants' | 'invitations' | 'join_requests';
    joinCodeVisible: boolean;
    joinCodeCopied: boolean;
    joinCodeRegenerating: boolean;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:tab', value: 'participants' | 'invitations' | 'join_requests'): void;
    (e: 'toggle-join-code'): void;
    (e: 'copy-join-code'): void;
    (e: 'regenerate-code'): void;
}>();
const { t } = useI18n();
</script>

<template>
    <div class="flex gap-2 overflow-x-auto border-b pb-2 text-sm">
        <button
            :class="['rounded px-3 py-1', activeTab === 'participants' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
            @click="emit('update:tab', 'participants')"
        >
            {{ t('groups.participants') }}
        </button>
        <button
            v-if="group.is_owner"
            :class="['rounded px-3 py-1', activeTab === 'invitations' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
            @click="emit('update:tab', 'invitations')"
        >
            {{ t('groups.invitations') }}
        </button>
        <button
            v-if="group.is_owner"
            :class="['rounded px-3 py-1', activeTab === 'join_requests' ? 'bg-primary text-primary-foreground' : 'bg-accent']"
            @click="emit('update:tab', 'join_requests')"
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
                            @click="emit('toggle-join-code')"
                            :aria-label="joinCodeVisible ? 'Ocultar código' : 'Mostrar código'"
                            :title="joinCodeVisible ? 'Ocultar código' : 'Mostrar código'"
                        >
                            <EyeOff v-if="joinCodeVisible" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="opacity-70 transition hover:opacity-100"
                            @click="emit('copy-join-code')"
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
                    @click="emit('regenerate-code')"
                >
                    <LoaderCircle v-if="joinCodeRegenerating" class="h-4 w-4 animate-spin" />
                    {{ group.join_code ? t('groups.new_code') : t('groups.generate_code') }}
                </button>
            </div>
        </div>
    </div>
</template>

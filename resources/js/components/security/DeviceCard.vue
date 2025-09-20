<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { TrustedDevice } from '@/interfaces/security';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    device: TrustedDevice;
    isCurrent: boolean;
    embedded?: boolean; // when true, parent card handles outer highlight
}>();

const emit = defineEmits<{
    (e: 'revoke', id: number): void;
    (e: 'rename', payload: { id: number; name: string }): void;
    (e: 'reveal-ip', id: number): void;
}>();

const renaming = ref(false);
const nameInput = ref('');
const { t } = useI18n();

function startRename() {
    renaming.value = true;
    nameInput.value = props.device.name || '';
}
function cancelRename() {
    renaming.value = false;
}
function submitRename() {
    emit('rename', { id: props.device.id, name: nameInput.value.trim() });
}

function revoke() {
    emit('revoke', props.device.id);
}
function revealIp() {
    emit('reveal-ip', props.device.id);
    if (!revealed.value) {
        revealed.value = true;
        setTimeout(() => (revealed.value = false), 15000);
    }
}

// local UI state for revealed IP (mirrors previous parent behavior)
const revealed = ref(false);
</script>

<template>
    <div
        class="relative flex h-full flex-col bg-card/60 p-4 shadow-sm ring-offset-background transition hover:border-foreground/20 hover:shadow-md dark:bg-card/30"
        :class="!embedded && isCurrent ? 'ring-2 ring-green-500/50 dark:ring-green-600/60' : ''"
    >
        <!-- HEADER -->
        <div class="mb-2 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="truncate text-sm font-semibold leading-snug" :title="device.name || 'Device #' + device.id">
                        <template v-if="!renaming">{{ device.name || 'Device #' + device.id }}</template>
                        <template v-else>
                            <input
                                v-model="nameInput"
                                maxlength="100"
                                class="w-48 rounded border bg-background px-2 py-1 text-[12px] focus:outline-none focus:ring-1 focus:ring-primary"
                                :placeholder="t('security.devices.name_placeholder', 'Nome do dispositivo')"
                                @keyup.enter.prevent="submitRename"
                                @keyup.esc.prevent="cancelRename"
                                autofocus
                            />
                        </template>
                    </h4>
                    <span
                        v-if="isCurrent"
                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-green-600/90 px-2 py-[3px] text-[10px] font-medium text-white shadow dark:bg-green-500/80"
                    >
                        <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 0 1 0 1.414l-7.25 7.25a1 1 0 0 1-1.414 0L3.293 9.957a1 1 0 0 1 1.414-1.414l3.043 3.043 6.543-6.543a1 1 0 0 1 1.414 0Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{ t('security.devices.current_badge', 'Atual') }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                    <span>{{ t('security.devices.last_used', 'Último uso') }}: {{ device.last_used_at }}</span>
                    <span>{{ t('security.devices.added', 'Adicionado') }} {{ device.created_at }}</span>
                </div>
            </div>
            <div class="flex flex-shrink-0 items-start">
                <!-- ACTIONS (desktop row) -->
                <div v-if="!renaming" class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" @click="startRename">{{ t('common.actions.rename', 'Renomear') }}</Button>
                    <Button size="sm" variant="destructive" :disabled="isCurrent" @click="revoke">{{
                        t('security.devices.revoke', 'Revogar')
                    }}</Button>
                </div>
                <div v-else class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" @click="submitRename">{{ t('common.actions.save', 'Salvar') }}</Button>
                    <Button size="sm" variant="ghost" @click="cancelRename">{{ t('common.actions.cancel', 'Cancelar') }}</Button>
                </div>
            </div>
        </div>

        <!-- BODY -->
        <div class="mt-auto space-y-2 pt-1 text-[12px]">
            <div class="flex flex-wrap items-center gap-2 text-muted-foreground">
                <span class="font-medium text-foreground/80">{{ device.os || '—' }}</span>
                <span v-if="device.browser" class="text-foreground/60">/ {{ device.browser }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 font-mono text-[11px] leading-tight">
                <span v-if="device.ip_address" :class="revealed ? '' : 'select-none blur-sm'">{{ device.ip_address }}</span>
                <span v-else>—</span>
                <button
                    v-if="device.ip_address"
                    type="button"
                    class="rounded bg-transparent px-1 text-[10px] text-blue-600 underline-offset-2 transition hover:bg-blue-50 hover:underline disabled:pointer-events-none disabled:opacity-50 dark:hover:bg-blue-900/30"
                    :disabled="revealed"
                    @click="revealIp"
                >
                    {{ revealed ? t('security.devices.visible', 'Visível...') : t('security.devices.show', 'Mostrar') }}
                </button>
            </div>
        </div>
    </div>
</template>

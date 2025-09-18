<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    group: any;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'create', payload: { user_id: number; excluded_user_id: number; onDone: () => void; onError: (msg: string) => void }): void;
    (e: 'delete', exclusionId: number): void;
}>();
const { t } = useI18n();

const form = ref<{ user_id: number | null; excluded_user_id: number | null }>({ user_id: null, excluded_user_id: null });
const submitting = ref(false);
const errorMsg = ref('');

function submit() {
    errorMsg.value = '';
    if (!form.value.user_id || !form.value.excluded_user_id) return;
    if (form.value.user_id === form.value.excluded_user_id) {
        errorMsg.value = t('groups.exclusion_same') || 'Participante não pode se excluir.';
        return;
    }
    submitting.value = true;
    emit('create', {
        user_id: form.value.user_id,
        excluded_user_id: form.value.excluded_user_id,
        onDone: () => {
            submitting.value = false;
            form.value.user_id = null;
            form.value.excluded_user_id = null;
        },
        onError: (m: string) => {
            submitting.value = false;
            errorMsg.value = m;
        },
    });
}
</script>

<template>
    <div v-if="group.exclusions" class="space-y-2 rounded border p-4">
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
                <button
                    class="ml-2 rounded bg-destructive px-2 py-0.5 text-[10px] text-destructive-foreground hover:bg-destructive/90"
                    type="button"
                    @click="emit('delete', ex.id)"
                >
                    {{ t('groups.remove') || 'Remover' }}
                </button>
            </li>
        </ul>
        <form
            v-if="group.participants && group.participants.length > 1"
            @submit.prevent="submit"
            class="mt-3 flex flex-col gap-2 rounded border-t pt-3 md:flex-row md:items-end"
        >
            <div class="flex flex-1 flex-col gap-1">
                <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{
                    t('groups.exclusion_giver') || 'Quem não pode tirar'
                }}</label>
                <select v-model="form.user_id" class="w-full rounded border bg-background px-2 py-1 text-sm">
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
                <select v-model="form.excluded_user_id" class="w-full rounded border bg-background px-2 py-1 text-sm">
                    <option :value="null">-</option>
                    <option v-for="p in group.participants" :key="p.id" :value="p.id" :disabled="p.id === form.user_id">
                        {{ p.name }}
                    </option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:pb-1">
                <button
                    type="submit"
                    :disabled="!form.user_id || !form.excluded_user_id || submitting"
                    class="rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground shadow disabled:opacity-50"
                >
                    {{ submitting ? t('groups.saving') || 'Salvando...' : t('groups.add_exclusion') || 'Adicionar' }}
                </button>
            </div>
        </form>
        <p v-if="errorMsg" class="text-xs text-destructive">{{ errorMsg }}</p>
    </div>
</template>

<script setup lang="ts">
import GiftRangeBadge from '@/components/groups/GiftRangeBadge.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    groupId: number;
    name: string;
    description: string | null;
    minGiftCents: number | null;
    maxGiftCents: number | null;
    currency: string | null;
    isOwner: boolean;
}

const props = defineProps<Props>();
const editing = ref(false);
const saving = ref(false);
const form = ref({
    name: props.name,
    description: props.description || '',
    min: props.minGiftCents !== null ? (props.minGiftCents / 100).toFixed(2) : '',
    max: props.maxGiftCents !== null ? (props.maxGiftCents / 100).toFixed(2) : '',
    currency: props.currency || 'BRL',
});

watch(
    () => [props.name, props.description, props.minGiftCents, props.maxGiftCents, props.currency],
    () => {
        if (!editing.value) {
            form.value.name = props.name;
            form.value.description = props.description || '';
            form.value.min = props.minGiftCents !== null ? (props.minGiftCents / 100).toFixed(2) : '';
            form.value.max = props.maxGiftCents !== null ? (props.maxGiftCents / 100).toFixed(2) : '';
            form.value.currency = props.currency || 'BRL';
        }
    },
);

const hasRange = computed(() => props.minGiftCents !== null || props.maxGiftCents !== null);

const toCents = (v: string): number | null => {
    if (!v.trim()) return null;
    const n = Number(v.replace(',', '.'));
    if (isNaN(n) || n < 0) return null;
    return Math.round(n * 100);
};

const startEdit = () => {
    if (props.isOwner) editing.value = true;
};
const cancel = () => {
    editing.value = false;
};
const submit = () => {
    if (!props.isOwner) return;
    saving.value = true;
    const payload: any = {
        name: form.value.name.trim(),
        description: form.value.description.trim() || null,
        min_gift_cents: toCents(form.value.min),
        max_gift_cents: toCents(form.value.max),
        currency: form.value.currency || 'BRL',
    };
    router.put(route('groups.update', props.groupId), payload, {
        only: ['group'],
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
            editing.value = false;
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-start justify-between gap-3">
            <div class="flex flex-1 flex-col gap-2">
                <div v-if="!editing" class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-semibold">{{ name }}</h1>
                    <GiftRangeBadge v-if="hasRange" :min-cents="minGiftCents" :max-cents="maxGiftCents" :currency="currency || 'BRL'" />
                    <span v-else class="text-xs italic text-muted-foreground">—</span>
                </div>
                <div v-else class="grid w-full gap-2 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Nome</label>
                        <input v-model="form.name" class="rounded border px-2 py-1 text-sm" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Moeda</label>
                        <select v-model="form.currency" class="rounded border px-2 py-1 text-sm">
                            <option value="BRL">BRL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1 md:col-span-2">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Descrição</label>
                        <textarea v-model="form.description" rows="2" class="w-full resize-y rounded border px-2 py-1 text-sm" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Mínimo</label>
                        <input v-model="form.min" type="text" inputmode="decimal" class="rounded border px-2 py-1 text-sm" placeholder="0,00" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Máximo</label>
                        <input v-model="form.max" type="text" inputmode="decimal" class="rounded border px-2 py-1 text-sm" placeholder="0,00" />
                    </div>
                </div>
                <p v-if="!editing && description" class="max-w-prose text-sm text-muted-foreground">{{ description }}</p>
                <p v-else-if="!editing && !description" class="text-xs italic text-muted-foreground">(sem descrição)</p>
            </div>
            <div v-if="isOwner" class="flex items-center gap-2">
                <button v-if="!editing" @click="startEdit" class="rounded bg-accent px-3 py-1 text-xs font-medium hover:bg-accent/70">Editar</button>
                <div v-else class="flex items-center gap-2">
                    <button
                        @click="submit"
                        :disabled="saving"
                        class="rounded bg-primary px-3 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ saving ? '...' : 'Salvar' }}
                    </button>
                    <button @click="cancel" :disabled="saving" class="rounded border px-3 py-1 text-xs hover:bg-accent disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface WishlistItem {
    id: number;
    item: string;
    note: string | null;
    url?: string | null;
}

interface Props {
    group: { id: number; name: string };
    items: WishlistItem[];
    order: string;
}

const props = defineProps<Props>();

// Create form
const createForm = useForm({
    item: '',
    note: '' as string | null,
    url: '' as string | null,
});

// Edit state
const editingId = ref<number | null>(null);
const editBuffers = reactive<Record<number, { item: string; note: string | null; url: string | null }>>({});

function startEdit(row: WishlistItem) {
    editingId.value = row.id;
    editBuffers[row.id] = { item: row.item, note: row.note ?? '', url: row.url ?? '' };
}

function cancelEdit() {
    if (editingId.value !== null) delete editBuffers[editingId.value];
    editingId.value = null;
}

function submitCreate() {
    createForm.post(route('groups.wishlist.store', { group: props.group.id }), {
        onSuccess: () => {
            createForm.reset();
        },
    });
}

function submitEdit(id: number) {
    const payload = editBuffers[id];
    router.put(route('groups.wishlist.update', { group: props.group.id, wishlist: id }), payload, {
        onSuccess: () => {
            delete editBuffers[id];
            if (editingId.value === id) editingId.value = null;
        },
    });
}

function remove(id: number) {
    if (!confirm('Remover item?')) return;
    router.delete(route('groups.wishlist.destroy', { group: props.group.id, wishlist: id }));
}

const hasItems = computed(() => props.items && props.items.length > 0);

function setOrder(order: 'created' | 'alpha') {
    if (order === props.order) return;
    router.get(route('groups.wishlist.index', { group: props.group.id, order }), {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head :title="`Wishlist - ${props.group.name}`" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Groups', href: route('groups.index') },
            { title: props.group.name, href: route('groups.wishlist.index', { group: props.group.id }) },
            { title: 'Wishlist', href: route('groups.wishlist.index', { group: props.group.id }) },
        ]"
    >
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Wishlist</h1>
                    <p class="text-xs text-muted-foreground">
                        Grupo: <span class="font-medium">{{ props.group.name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center gap-1 rounded-full border px-1 py-0.5">
                        <button
                            type="button"
                            class="rounded px-2 py-1 text-xs font-medium"
                            :class="props.order === 'created' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                            @click="setOrder('created')"
                        >
                            Recentes
                        </button>
                        <button
                            type="button"
                            class="rounded px-2 py-1 text-xs font-medium"
                            :class="props.order === 'alpha' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                            @click="setOrder('alpha')"
                        >
                            A-Z
                        </button>
                    </div>
                    <Link :href="route('groups.index')" class="text-xs text-primary hover:underline">Voltar</Link>
                </div>
            </div>

            <!-- Add form -->
            <form @submit.prevent="submitCreate" class="space-y-3 rounded-xl border bg-card p-4">
                <div class="flex flex-col gap-2">
                    <input
                        v-model="createForm.item"
                        type="text"
                        placeholder="Item"
                        class="rounded border px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        required
                        maxlength="255"
                    />
                    <input
                        v-model="createForm.note"
                        type="text"
                        placeholder="Nota (opcional)"
                        class="rounded border px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        maxlength="255"
                    />
                    <div class="flex flex-col gap-1">
                        <input
                            v-model="createForm.url"
                            type="url"
                            placeholder="Link do produto (Amazon etc.)"
                            class="rounded border px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            maxlength="255"
                        />
                        <p class="text-xs text-muted-foreground">Esqueci http? Adiciono https automaticamente.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="rounded bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        Adicionar
                    </button>
                    <div v-if="createForm.errors.item" class="text-sm text-red-600">{{ createForm.errors.item }}</div>
                </div>
            </form>

            <!-- List -->
            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left dark:bg-gray-700/50">
                        <tr>
                            <th class="w-1/3 px-4 py-2">Item</th>
                            <th class="w-1/4 px-4 py-2">Nota</th>
                            <th class="w-1/4 px-4 py-2">Link</th>
                            <th class="px-4 py-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!hasItems">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">Nenhum item ainda.</td>
                        </tr>
                        <tr v-for="row in props.items" :key="row.id" class="border-t border-gray-100 dark:border-gray-700/60">
                            <template v-if="editingId === row.id">
                                <td class="px-4 py-2 align-top">
                                    <input
                                        v-model="editBuffers[row.id].item"
                                        type="text"
                                        class="w-full rounded border px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                        required
                                        maxlength="255"
                                    />
                                </td>
                                <td class="px-4 py-2 align-top">
                                    <input
                                        v-model="editBuffers[row.id].note"
                                        type="text"
                                        class="w-full rounded border px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                        maxlength="255"
                                    />
                                </td>
                                <td class="px-4 py-2 align-top">
                                    <input
                                        v-model="editBuffers[row.id].url"
                                        type="url"
                                        class="w-full rounded border px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                        maxlength="255"
                                    />
                                </td>
                                <td class="space-x-2 whitespace-nowrap px-4 py-2 text-right">
                                    <button
                                        @click="submitEdit(row.id)"
                                        class="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-500"
                                    >
                                        Salvar
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        type="button"
                                        class="rounded bg-gray-500 px-3 py-1 text-xs font-medium text-white hover:bg-gray-400"
                                    >
                                        Cancelar
                                    </button>
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-4 py-2 align-top">
                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ row.item }}</span>
                                </td>
                                <td class="px-4 py-2 align-top text-gray-600 dark:text-gray-400">
                                    {{ row.note || '—' }}
                                </td>
                                <td class="px-4 py-2 align-top text-xs">
                                    <a
                                        v-if="row.url"
                                        :href="row.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="break-all text-primary hover:underline"
                                        >Link</a
                                    >
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="space-x-2 whitespace-nowrap px-4 py-2 text-right">
                                    <button
                                        @click="startEdit(row)"
                                        class="rounded bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-500"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="remove(row.id)"
                                        class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-500"
                                    >
                                        Remover
                                    </button>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-xs text-muted-foreground">
                Somente você vê sua própria wishlist por enquanto. Regras de visibilidade após o sorteio serão adicionadas.
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>

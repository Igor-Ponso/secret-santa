<script setup lang="ts">
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';

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
const { t } = useI18n();

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
    // Prevent sending empty/whitespace item
    createForm.item = createForm.item.trim();
    if (!createForm.item) return;
    if (createForm.url && !/^https?:\/\//i.test(createForm.url)) {
        // Let backend also normalize, but we can optimistically add here
        createForm.url = 'https://' + createForm.url.trim();
    }
    createForm.post(route('groups.wishlist.store', { group: props.group.id }), {
        preserveScroll: true,
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

// Batch mode state
interface DraftItem {
    item: string;
    note: string;
    url: string;
}
const batchMode = ref(false);
const drafts = ref<DraftItem[]>([{ item: '', note: '', url: '' }]);

function addDraft() {
    if (drafts.value.length >= 5) return;
    drafts.value.push({ item: '', note: '', url: '' });
}
function removeDraft(i: number) {
    if (drafts.value.length === 1) return;
    drafts.value.splice(i, 1);
}
function resetDrafts() {
    drafts.value = [{ item: '', note: '', url: '' }];
}
function submitBatch() {
    const payload = drafts.value.map((d) => ({ item: d.item.trim(), note: d.note.trim(), url: d.url.trim() })).filter((d) => d.item.length);
    if (!payload.length) return;
    router.post(
        route('groups.wishlist.store.batch', { group: props.group.id }),
        { items: payload },
        {
            preserveScroll: true,
            onSuccess: () => {
                resetDrafts();
                batchMode.value = false;
            },
        },
    );
}

function setOrder(order: 'created' | 'alpha') {
    if (order === props.order) return;
    router.get(route('groups.wishlist.index', { group: props.group.id, order }), {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head :title="`${t('wishlist.title')} - ${props.group.name}`" />
    <AppLayout
        :breadcrumbs="[
            { title: t('common.misc.groups'), href: route('groups.index') },
            { title: props.group.name, href: route('groups.wishlist.index', { group: props.group.id }) },
            { title: t('wishlist.title'), href: route('groups.wishlist.index', { group: props.group.id }) },
        ]"
    >
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ t('wishlist.title') }}</h1>
                    <p class="text-xs text-muted-foreground">
                        {{ t('common.labels.group') }}: <span class="font-medium">{{ props.group.name }}</span>
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
                            {{ t('common.misc.wishlist_recent') }}
                        </button>
                        <button
                            type="button"
                            class="rounded px-2 py-1 text-xs font-medium"
                            :class="props.order === 'alpha' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
                            @click="setOrder('alpha')"
                        >
                            {{ t('common.misc.wishlist_alpha') }}
                        </button>
                    </div>
                    <Link :href="route('groups.index')" class="text-xs text-primary hover:underline">{{ t('common.misc.wishlist_back') }}</Link>
                </div>
            </div>

            <!-- Add form / mode switcher -->
            <div class="mb-2 flex flex-col gap-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold">
                        {{ batchMode ? t('wishlist.add_multiple') : t('common.misc.wishlist_add') }}
                    </h2>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="select-none text-muted-foreground">{{ t('wishlist.multi_mode_label') }}</span>
                        <Switch :model-value="batchMode" @update:model-value="(v) => (batchMode = v)">
                            <template #thumb>
                                <span class="block h-3 w-3 rounded-full bg-primary-foreground"></span>
                            </template>
                        </Switch>
                        <button
                            v-if="batchMode"
                            type="button"
                            class="rounded border px-2 py-1 text-[10px] font-medium hover:bg-accent"
                            @click="batchMode = false"
                        >
                            {{ t('common.actions.cancel') }}
                        </button>
                    </div>
                </div>
                <p v-if="batchMode" class="text-[11px] leading-snug text-muted-foreground">{{ t('wishlist.multi_hint') }}</p>
            </div>
            <form v-if="!batchMode" @submit.prevent="submitCreate" class="space-y-3 rounded-xl border bg-card p-4" novalidate>
                <div class="flex flex-col gap-3">
                    <label class="flex flex-col gap-1 text-xs font-medium">
                        <span>{{ t('common.misc.wishlist_item') }}</span>
                        <input
                            v-model="createForm.item"
                            type="text"
                            :placeholder="t('common.misc.wishlist_item')"
                            class="rounded border bg-background px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            required
                            maxlength="255"
                            autocomplete="off"
                        />
                        <span v-if="createForm.errors.item" class="text-[11px] font-normal text-red-600">{{ createForm.errors.item }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-medium">
                        <span>{{ t('common.misc.wishlist_note') }}</span>
                        <input
                            v-model="createForm.note"
                            type="text"
                            :placeholder="t('common.misc.wishlist_note') + ' (' + t('common.actions.optional', 'Optional') + ')'"
                            class="rounded border bg-background px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            maxlength="255"
                        />
                        <span v-if="createForm.errors.note" class="text-[11px] font-normal text-red-600">{{ createForm.errors.note }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-medium">
                        <span>{{ t('common.misc.wishlist_link') }}</span>
                        <input
                            v-model="createForm.url"
                            type="url"
                            :placeholder="t('common.misc.wishlist_link') + ' (https://...)'"
                            class="rounded border bg-background px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            maxlength="255"
                            autocomplete="off"
                            inputmode="url"
                        />
                        <span v-if="createForm.errors.url" class="text-[11px] font-normal text-red-600">{{ createForm.errors.url }}</span>
                        <p class="text-[11px] text-muted-foreground">
                            {{ t('wishlist.autofix_scheme', 'If you forget http, we add https automatically.') }}
                        </p>
                    </label>
                </div>
                <div class="flex items-center gap-3 pt-1">
                    <button
                        type="submit"
                        :disabled="createForm.processing || !createForm.item.trim()"
                        class="rounded bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ t('common.misc.wishlist_add') }}
                    </button>
                    <button
                        type="button"
                        class="rounded border px-3 py-2 text-[11px] font-medium hover:bg-accent"
                        @click="createForm.reset()"
                        :disabled="createForm.processing"
                    >
                        {{ t('common.actions.cancel') }}
                    </button>
                </div>
            </form>
            <form v-else @submit.prevent="submitBatch" class="space-y-4 rounded-xl border bg-card p-4" novalidate>
                <div class="flex flex-col gap-4">
                    <div v-for="(d, i) in drafts" :key="i" class="space-y-2 rounded-md border p-3">
                        <div class="flex gap-2">
                            <input
                                v-model="d.item"
                                :placeholder="t('wishlist.multi_item_placeholder')"
                                class="flex-1 rounded border bg-background px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                maxlength="255"
                            />
                            <button
                                type="button"
                                @click="removeDraft(i)"
                                :disabled="drafts.length === 1"
                                class="rounded border px-2 py-1 text-[10px] font-medium hover:bg-accent disabled:opacity-40"
                                aria-label="Remove row"
                            >
                                ×
                            </button>
                        </div>
                        <div class="grid gap-2 md:grid-cols-2">
                            <input
                                v-model="d.note"
                                :placeholder="t('wishlist.multi_note_placeholder')"
                                class="rounded border bg-background px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                maxlength="255"
                            />
                            <input
                                v-model="d.url"
                                :placeholder="t('wishlist.multi_url_placeholder')"
                                class="rounded border bg-background px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                maxlength="255"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 pt-1">
                    <button
                        type="button"
                        @click="addDraft"
                        :disabled="drafts.length >= 5"
                        class="rounded border px-3 py-2 text-[11px] font-medium hover:bg-accent disabled:opacity-40"
                    >
                        + {{ t('common.misc.add') || 'Add' }}
                    </button>
                    <button
                        type="submit"
                        :disabled="!drafts.some((d) => d.item.trim().length)"
                        class="rounded bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ t('wishlist.multi_submit') }}
                    </button>
                    <button
                        type="button"
                        @click="resetDrafts"
                        class="rounded border px-3 py-2 text-[11px] font-medium hover:bg-accent"
                        :disabled="drafts.length === 1 && !drafts[0].item && !drafts[0].note && !drafts[0].url"
                    >
                        {{ t('common.actions.cancel') }}
                    </button>
                </div>
            </form>

            <!-- List -->
            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="w-1/3 px-4 py-2">{{ t('common.misc.wishlist_item') }}</th>
                            <th class="w-1/4 px-4 py-2">{{ t('common.misc.wishlist_note') }}</th>
                            <th class="w-1/4 px-4 py-2">{{ t('common.misc.wishlist_link') }}</th>
                            <th class="px-4 py-2 text-right">{{ t('common.misc.wishlist_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!hasItems">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ t('common.misc.wishlist_empty') }}</td>
                        </tr>
                        <tr v-for="row in props.items" :key="row.id" class="border-t border-gray-100 dark:border-gray-700/60">
                            <template v-if="editingId === row.id">
                                <td class="px-4 py-2 align-top">
                                    <input
                                        v-model="editBuffers[row.id].item"
                                        type="text"
                                        class="w-full rounded border bg-background px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                        required
                                        maxlength="255"
                                    />
                                </td>
                                <td class="px-4 py-2 align-top">
                                    <input
                                        v-model="editBuffers[row.id].note"
                                        type="text"
                                        class="w-full rounded border bg-background px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                        maxlength="255"
                                    />
                                </td>
                                <td class="px-4 py-2 align-top">
                                    <input
                                        v-model="editBuffers[row.id].url"
                                        type="url"
                                        class="w-full rounded border bg-background px-2 py-1 text-xs focus:border-primary focus:ring-primary"
                                        maxlength="255"
                                    />
                                </td>
                                <td class="space-x-2 whitespace-nowrap px-4 py-2 text-right">
                                    <button
                                        @click="submitEdit(row.id)"
                                        class="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-500"
                                    >
                                        {{ t('common.actions.save') }}
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        type="button"
                                        class="rounded bg-gray-500 px-3 py-1 text-xs font-medium text-white hover:bg-gray-400"
                                    >
                                        {{ t('common.actions.cancel') }}
                                    </button>
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-4 py-2 align-top">
                                    <span class="font-medium">{{ row.item }}</span>
                                </td>
                                <td class="px-4 py-2 align-top text-muted-foreground">
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
                                        {{ t('common.actions.edit') }}
                                    </button>
                                    <button
                                        @click="remove(row.id)"
                                        class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-500"
                                    >
                                        {{ t('common.actions.delete') }}
                                    </button>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-xs text-muted-foreground">
                {{ t('common.misc.wishlist_visibility_hint') }}
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>

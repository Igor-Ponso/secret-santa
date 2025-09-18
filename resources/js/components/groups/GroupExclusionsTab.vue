<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, PlusCircle, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Exclusion {
    id: number;
    user: { id: number; name: string };
    excluded_user: { id: number; name: string };
    user_id: number;
    excluded_user_id: number;
}

interface Participant {
    id: number;
    name: string;
}

interface Props {
    group: any; // expects: id, exclusions: Exclusion[], participants (maybe) or we derive from group payload
}

const props = defineProps<Props>();
const { t } = useI18n();

// Ensure we have participants list (owner + accepted invitations).
const participants = computed<Participant[]>(() => props.group.participants || []);

const form = useForm({ user_id: null as number | null, excluded_user_id: null as number | null, reciprocal: false });

const canSubmit = computed(() => !!form.user_id && !!form.excluded_user_id && form.user_id !== form.excluded_user_id);

function resetForm() {
    form.user_id = null;
    form.excluded_user_id = null;
    form.reciprocal = false;
    form.clearErrors();
}

function submit() {
    if (!canSubmit.value) return;
    form.post(route('groups.exclusions.store', props.group.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Force reload exclusions via a partial reload or optimistic update
            router.reload({ only: ['group'] });
            resetForm();
        },
    });
}

function removeExclusion(e: Exclusion) {
    if (!confirm(t('groups.exclusions_remove_confirm'))) return;
    router.delete(route('groups.exclusions.destroy', { group: props.group.id, exclusion: e.id }), {
        preserveScroll: true,
    });
}

// Detect impossibility heuristics
// Rule 1: Any participant with exclusions covering all others => impossible
const impossibility = computed(() => {
    const list: Exclusion[] = props.group.exclusions || [];
    if (!participants.value.length) return null;
    const map: Record<number, Set<number>> = {};
    list.forEach((x: Exclusion) => {
        map[x.user_id] = map[x.user_id] || new Set();
        map[x.user_id].add(x.excluded_user_id);
    });
    const total = participants.value.length;
    const offenders: { user: Participant; count: number }[] = [];
    participants.value.forEach((p) => {
        const exCount = map[p.id]?.size || 0;
        if (exCount >= total - 1 && total > 1) {
            offenders.push({ user: p, count: exCount });
        }
    });
    if (offenders.length) {
        return {
            type: 'hard',
            offenders,
        } as const;
    }
    // Rule 2: Density threshold — if exclusions > (n*(n-1))/2 * 0.6 maybe hard (heuristic)
    const maxDirected = total * (total - 1); // ordered pairs
    if (total > 3 && list.length / maxDirected > 0.6) {
        return {
            type: 'dense',
        } as const;
    }
    return null;
});

const filteredParticipantsForExcluded = computed(() => {
    return participants.value.filter((p) => p.id !== form.user_id);
});

const existingKey = (a: number, b: number) => `${a}-${b}`;
const existingPairs = computed(() => new Set((props.group.exclusions || []).map((e: Exclusion) => existingKey(e.user_id, e.excluded_user_id))));

const alreadyExists = computed(() => {
    if (!form.user_id || !form.excluded_user_id) return false;
    return existingPairs.value.has(existingKey(form.user_id, form.excluded_user_id));
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-sm font-semibold">{{ t('groups.exclusions') }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ t('groups.exclusions_help') }}</p>
        </div>

        <div class="rounded border p-3">
            <form @submit.prevent="submit" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex flex-1 flex-col gap-1">
                    <label class="text-xs font-medium">{{ t('groups.exclusions_who') }}</label>
                    <select
                        v-model.number="form.user_id"
                        class="rounded border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 dark:border-neutral-600 dark:bg-neutral-800"
                    >
                        <option :value="null">—</option>
                        <option v-for="p in participants" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="flex flex-1 flex-col gap-1">
                    <label class="text-xs font-medium">{{ t('groups.exclusions_cannot_draw') }}</label>
                    <select
                        v-model.number="form.excluded_user_id"
                        class="rounded border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 dark:border-neutral-600 dark:bg-neutral-800"
                    >
                        <option :value="null">—</option>
                        <option v-for="p in filteredParticipantsForExcluded" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="checkbox" v-model="form.reciprocal" class="h-3 w-3 rounded border-neutral-400 text-primary focus:ring-primary" />
                        <span>{{ t('groups.exclusions_add_inverse') }}</span>
                    </label>
                    <button
                        type="submit"
                        :disabled="!canSubmit || form.processing || alreadyExists"
                        class="inline-flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
                    >
                        <PlusCircle class="h-4 w-4" />
                        {{ t('groups.exclusions_add') }}
                    </button>
                </div>
            </form>
            <p v-if="alreadyExists" class="mt-1 text-[11px] text-amber-600">{{ t('groups.exclusions_exists') }}</p>
            <p v-if="form.errors.user_id || form.errors.excluded_user_id" class="mt-1 text-[11px] text-red-600">
                {{ form.errors.user_id || form.errors.excluded_user_id }}
            </p>
        </div>

        <div
            v-if="impossibility"
            class="flex items-start gap-2 rounded border border-red-300 bg-red-50 p-3 text-xs text-red-700 dark:border-red-600 dark:bg-red-900/30 dark:text-red-200"
        >
            <AlertTriangle class="mt-0.5 h-4 w-4 flex-shrink-0" />
            <div>
                <p class="font-medium">
                    {{ t(impossibility.type === 'hard' ? 'groups.exclusions_impossible_offender' : 'groups.exclusions_dense_warning') }}
                </p>
                <ul v-if="impossibility.type === 'hard'" class="mt-1 list-disc pl-4">
                    <li v-for="o in impossibility.offenders" :key="o.user.id">
                        {{ t('groups.exclusions_offender_item', { name: o.user.name, count: o.count }) }}
                    </li>
                </ul>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ t('groups.exclusions_rules') }}</h3>
            <div v-if="(props.group.exclusions || []).length === 0" class="text-xs text-muted-foreground">{{ t('groups.exclusions_none') }}</div>
            <ul v-else class="flex flex-col divide-y">
                <li v-for="e in props.group.exclusions" :key="e.id" class="flex items-center justify-between py-1.5 text-sm">
                    <span class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded bg-accent px-2 py-0.5 text-[11px] font-medium">
                            <strong>{{ e.user.name }}</strong>
                            <span class="opacity-70">↛</span>
                            <strong>{{ e.excluded_user.name }}</strong>
                        </span>
                    </span>
                    <button
                        @click="removeExclusion(e)"
                        class="rounded p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30"
                        :aria-label="t('groups.exclusions_remove')"
                        :title="t('groups.exclusions_remove')"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

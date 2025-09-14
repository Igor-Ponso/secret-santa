<script setup lang="ts">
import { Copy } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ groupId: number; isOwner: boolean }>();
const { t } = useI18n();

const inviteLink = ref('');
const inviteError = ref<string>('');
const copied = ref(false);
const loading = ref(false);

const fullLink = computed(() => inviteLink.value);

const fetchInviteLink = async (): Promise<void> => {
    if (!props.isOwner) return;
    loading.value = true;
    inviteError.value = '';
    try {
        const res = await fetch(`/groups/${props.groupId}/invitation-link`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!res.ok || !data.link) {
            throw new Error(data.error || t('errors.fetch_invite_link') || 'Failed to load invite link');
        }
        inviteLink.value = data.link;
    } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        inviteError.value = message;
    } finally {
        loading.value = false;
    }
};

const copyInviteLink = (): void => {
    if (!fullLink.value) return;
    navigator.clipboard.writeText(fullLink.value).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    });
};

onMounted(fetchInviteLink);
</script>

<template>
    <div v-if="isOwner" class="mb-6 rounded border border-primary/30 bg-primary/5 p-4">
        <h2 class="mb-2 flex items-center gap-2 text-base font-semibold">
            {{ t('groups.invite_link') }}
        </h2>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                :value="fullLink"
                readonly
                class="w-full rounded border bg-background/80 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                :placeholder="loading ? t('common.loading') : t('groups.invite_link_placeholder')"
            />
            <button
                type="button"
                @click="copyInviteLink"
                class="ml-2 flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                :disabled="!fullLink || loading"
            >
                <Copy class="h-4 w-4" />
                {{ copied ? t('common.copied') : t('common.copy') }}
            </button>
            <button
                v-if="!loading && !fullLink"
                type="button"
                @click="fetchInviteLink"
                class="ml-2 flex items-center gap-1 rounded bg-primary/80 px-3 py-1.5 text-xs text-primary-foreground hover:bg-primary/90"
            >
                {{ t('groups.generate_invite_link') }}
            </button>
        </div>
        <div v-if="inviteError" class="mt-2 text-xs text-destructive">{{ inviteError }}</div>
    </div>
</template>

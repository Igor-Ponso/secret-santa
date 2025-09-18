<script setup lang="ts">
import JoinRequestsList from '@/components/groups/JoinRequestsList.vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import { useDateFormat } from '@/lib/formatDate';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    group: any;
    joinRequestActing: { id: number | null; action: 'approve' | 'deny' | null };
}

const props = defineProps<Props>();
const { t } = useI18n();
const { formatDate } = useDateFormat();
const jrSearch = ref('');
let searchTimeout: any = null;

const approveJoin = (id: number) => {
    router.post(route('groups.join_requests.approve', { group: props.group.id, joinRequest: id }), {}, { preserveScroll: true });
};
const denyJoin = (id: number) => {
    router.post(route('groups.join_requests.deny', { group: props.group.id, joinRequest: id }), {}, { preserveScroll: true });
};

watch(jrSearch, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => onJrPage(1), 300);
});

function onJrPage(page: number) {
    router.get(
        route('groups.show', props.group.id),
        {
            tab: 'join_requests',
            invite_page: props.group.invitations_meta?.current_page || 1,
            invite_search: '',
            jr_page: page,
            jr_search: jrSearch.value,
        },
        { only: ['group'], preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <div>
        <JoinRequestsList
            :join-requests="props.group.join_requests || []"
            :jr-search="jrSearch"
            :join-request-acting="props.joinRequestActing"
            :format-date="formatDate"
            @update:jrSearch="(v) => (jrSearch = v)"
            @approve="approveJoin"
            @deny="denyJoin"
        >
            <template #pagination>
                <div v-if="props.group.join_requests_meta && props.group.join_requests_meta.last_page > 1" class="pt-2">
                    <Pagination
                        :page="props.group.join_requests_meta.current_page"
                        :items-per-page="props.group.join_requests_meta.per_page"
                        :total="props.group.join_requests_meta.total"
                        @update:page="onJrPage"
                    />
                </div>
            </template>
        </JoinRequestsList>
        <p v-if="jrSearch && !(props.group.join_requests || []).length" class="text-xs text-muted-foreground">
            {{ t('groups.no_results').replace(':query', jrSearch) }}
        </p>
    </div>
</template>

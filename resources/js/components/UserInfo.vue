<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User | null | undefined;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(() => !!props.user && typeof props.user.avatar === 'string' && props.user.avatar.trim().length > 0);
</script>

<template>
    <template v-if="user">
        <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
            <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
            <AvatarFallback class="rounded-lg text-black dark:text-white">
                {{ getInitials(user.name || '') }}
            </AvatarFallback>
        </Avatar>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">{{ user.name }}</span>
            <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
        </div>
    </template>
    <template v-else>
        <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
            <AvatarFallback class="rounded-lg text-black dark:text-white">--</AvatarFallback>
        </Avatar>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">&nbsp;</span>
        </div>
    </template>
</template>

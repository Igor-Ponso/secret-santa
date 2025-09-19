<script setup lang="ts">
import type { Recipient, WishlistItem } from '@/interfaces/group';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ recipient: Recipient | null; wishlist: WishlistItem[]; loading: boolean; hasDraw: boolean }>();
const { t } = useI18n();

const hasWishlist = computed(() => (props.wishlist || []).length > 0);
</script>

<template>
    <div>
        <div v-if="hasDraw" class="space-y-2">
            <p class="text-xs text-muted-foreground">{{ t('groups.your_recipient') }}</p>
            <div v-if="loading" class="text-xs">{{ t('groups.loading') }}</div>
            <div v-else-if="recipient" class="flex flex-col gap-2 rounded bg-accent px-3 py-2 text-sm font-medium">
                <span>{{ recipient.name }}</span>
                <div v-if="hasWishlist" class="rounded border bg-background/70 p-2">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ t('groups.wishlist') }}
                    </p>
                    <ul class="max-h-40 space-y-1 overflow-auto pr-1">
                        <li v-for="w in wishlist" :key="w.id" class="rounded bg-accent/40 px-2 py-1 text-sm leading-tight">
                            <span class="font-medium">{{ w.item }}</span>
                            <a v-if="w.url" :href="w.url" target="_blank" rel="noopener" class="ml-2 text-xs underline hover:text-primary">link</a>
                            <div v-if="w.note" class="mt-0.5 text-xs italic opacity-80">{{ w.note }}</div>
                        </li>
                    </ul>
                </div>
                <div v-else class="text-xs text-muted-foreground">{{ t('groups.empty_wishlist') }}</div>
            </div>
            <div v-else class="text-xs text-muted-foreground">
                {{ t('groups.recipient_not_found') }}
            </div>
        </div>
        <div v-else class="text-xs text-muted-foreground">{{ t('groups.not_drawn_yet') }}</div>
    </div>
</template>

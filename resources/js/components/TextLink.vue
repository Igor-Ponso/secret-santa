<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import type { Method } from '@inertiajs/core';

interface Props {
    href: string;
    tabindex?: number;
    method?: Method; // constrain to Inertia verbs
    as?: string;
}

withDefaults(defineProps<Props>(), {
    method: undefined,
    as: undefined,
    tabindex: undefined,
});
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-neutral-500"
    >
        <slot />
    </Link>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';

const providers = [
    { name: 'google', label: 'Google' },
    { name: 'github', label: 'GitHub' },
    { name: 'linkedin', label: 'LinkedIn' },
    { name: 'microsoft', label: 'Microsoft' },
];

const redirectToProvider = (provider: string) => {
    window.location.href = route('social.login', provider);
};
</script>

<template>
    <div class="flex w-full items-center justify-center gap-4">
        <Button
            v-for="provider in providers"
            :key="provider.name"
            :aria-label="`Continue with ${provider.label}`"
            @click="redirectToProvider(provider.name)"
            variant="ghost"
            size="icon"
            class="border border-muted bg-white transition hover:bg-muted/20"
        >
            <img :src="`/icons/${provider.name}-icon.svg`" :alt="`${provider.label} logo`" class="h-5 w-5" />
        </Button>
    </div>
</template>

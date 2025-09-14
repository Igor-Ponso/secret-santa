<script setup lang="ts">
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

/**
 * Determine the hero image and caption based on the current route component.
 */
const visual = computed(() => {
    const routeName = page.component;

    if (routeName.includes('Register')) {
        return {
            image: new URL('@assets/illustrations/naughty-nice-list.png', import.meta.url).href,
            caption: "Join the list. Naughty or Nice, there's always room for more.",
        };
    }

    if (routeName.includes('Login')) {
        return {
            image: new URL('@assets/illustrations/login-hero.png', import.meta.url).href,
            caption: 'Clock in and let the magic begin.',
        };
    }

    // Fallback visual for other auth-related pages
    return {
        image: new URL('@assets/illustrations/mascot-hero.png', import.meta.url).href,
        caption: 'Welcome!',
    };
});

const showTopBar = computed(() => {
    const routeName = page.component as string;
    return /Login|Register/.test(routeName);
});

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div v-if="showTopBar" class="absolute right-4 top-4 z-30 flex items-center gap-3">
            <ThemeToggle />
            <LanguageSwitcher />
        </div>
        <!-- Left visual panel (hidden on small screens) -->
        <div
            class="relative hidden h-full flex-col bg-gradient-to-br from-red-800 via-red-600 to-red-500 px-12 pb-12 pt-20 text-white dark:border-r lg:flex"
        >
            <div class="relative z-10 flex h-full w-full flex-col items-center justify-center gap-10 text-center">
                <img :src="visual.image" alt="Auth Visual" class="w-[420px] max-w-full drop-shadow-[0_12px_32px_rgba(0,0,0,0.45)]" />
                <p class="max-w-md text-xl font-medium italic leading-snug text-white/95 md:text-2xl">{{ visual.caption }}</p>
            </div>
            <Link
                :href="route('home')"
                class="absolute left-8 top-6 rounded-full bg-white/20 px-4 py-1.5 text-sm font-semibold text-white/90 backdrop-blur transition hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/50"
            >
                ← {{ 'Home' }}
            </Link>
        </div>

        <!-- Right side (form slot) -->
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-7 sm:w-[380px]">
                <div class="flex flex-col space-y-3 text-center">
                    <h1 class="text-2xl font-semibold tracking-tight md:text-3xl" v-if="title">{{ title }}</h1>
                    <p class="text-base text-muted-foreground md:text-lg" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
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

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <!-- Left visual panel (hidden on small screens) -->
        <div class="relative hidden h-full flex-col bg-gradient-to-b from-red-800 via-red-600 to-red-500 p-10 text-white dark:border-r lg:flex">
            <div class="absolute right-4 top-4">
                <ThemeToggle />
            </div>
            <div class="relative z-20 flex h-full w-full flex-col items-center justify-center text-center">
                <!-- Centered illustration and caption -->
                <div class="flex flex-col items-center justify-center gap-6">
                    <img :src="visual.image" alt="Auth Visual" class="w-3/4 max-w-md drop-shadow-lg" />
                    <p class="text-lg italic text-white/95">{{ visual.caption }}</p>
                </div>

                <!-- Back to home link (bottom fixed) -->
                <Link
                    :href="route('home')"
                    class="text-md absolute bottom-10 font-extrabold text-white/90 underline underline-offset-4 hover:text-white"
                >
                    ← Back to home
                </Link>
            </div>
        </div>

        <!-- Right side (form slot) -->
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

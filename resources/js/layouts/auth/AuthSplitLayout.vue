<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { DotLottieVue } from '@lottiefiles/dotlottie-vue'

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="relative hidden h-full flex-col bg-gradient-to-b from-red-800 via-red-600 to-red-500 p-10 text-white dark:border-r lg:flex">
            <!-- Overlay escuro sutil por cima (opcional) -->
            <div class="absolute inset-0 bg-black/30" />

            <!-- Conteúdo principal -->
            <div class="relative z-20 flex flex-col h-full">
                <Link :href="route('home')" class="flex items-center text-lg font-medium">
                    <DotLottieVue style="height: 60px; width: 60px" autoplay loop src="https://lottie.host/322f7625-05c2-469a-b338-01ac43139cb4/wHA2h57HAm.lottie" />
                    {{ name }}
                </Link>
               
            </div>
        </div>

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


<script setup lang="ts">
// Image assets live in resources/assets, not under resources/js, so use the new @assets alias.
import ThemeToggle from '@/components/ThemeToggle.vue';
import mascotUrl from '@assets/illustrations/mascot-hero.png';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Gift } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

// Access typed page props (ensures $page typing through usePage if needed)
const page = usePage();
const { t } = useI18n();
</script>

<template>
    <Head title="Welcome" />

    <div
        class="relative flex min-h-screen flex-col items-center justify-between overflow-hidden bg-gradient-to-b from-red-700 via-red-600 to-red-500 text-white"
    >
        <!-- Navbar -->
        <header class="relative z-10 flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:gap-6 sm:px-6 sm:py-5">
            <h1 class="flex shrink-0 items-center gap-2" aria-label="Secret Santa">
                <Gift class="h-7 w-7 text-white sm:h-8 sm:w-8" />
                <span class="hidden text-2xl font-bold tracking-wide sm:inline">{{ t('landing.brand') }}</span>
            </h1>
            <div class="flex min-w-0 flex-1 items-center justify-end gap-3 sm:gap-5">
                <nav class="flex items-center gap-3 text-xs font-medium sm:gap-6 sm:text-sm">
                    <Link v-if="page.props.auth?.user" :href="route('dashboard')" class="whitespace-nowrap hover:underline">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="whitespace-nowrap hover:underline">{{ t('landing.login') }}</Link>
                        <Link :href="route('register')" class="whitespace-nowrap hover:underline">{{ t('landing.register') }}</Link>
                    </template>
                </nav>
                <ThemeToggle />
            </div>
        </header>

        <!-- Hero Section -->
        <section
            class="relative z-10 grid w-full max-w-6xl items-center gap-8 px-5 py-8 sm:gap-10 sm:px-6 sm:py-10 lg:grid-cols-[1fr_1.2fr] lg:gap-12 lg:py-12"
        >
            <!-- Mascot with contrast background -->
            <div class="flex justify-center lg:justify-start">
                <div class="relative inline-block rounded-[36%] bg-[#fff8f3] p-3 drop-shadow-[0_12px_16px_rgba(0,0,0,0.25)] sm:p-4">
                    <img
                        :src="mascotUrl"
                        alt="Santa Claus mascot"
                        class="max-h-[40vh] w-full max-w-[260px] object-contain transition-transform duration-300 ease-in-out hover:scale-[1.01] sm:max-h-none sm:max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl 2xl:max-w-2xl"
                    />
                </div>
            </div>

            <!-- Text -->
            <div class="max-w-xl space-y-5 text-white">
                <h2 class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">{{ t('landing.headline') }}</h2>

                <p class="text-xl leading-relaxed text-white/90">{{ t('landing.tagline') }}</p>

                <p class="text-base leading-relaxed text-white/80">{{ t('landing.pitch') }}</p>

                <div class="mt-4 flex flex-col items-start gap-3">
                    <Link
                        :href="route('register')"
                        class="rounded-lg bg-[#ffeaea] px-8 py-3 text-lg font-semibold text-red-800 shadow-lg transition hover:bg-red-100"
                    >
                        {{ t('landing.cta_create') }}
                    </Link>

                    <Link :href="route('login')" class="text-sm text-white underline hover:text-red-200"> {{ t('landing.cta_have_account') }} </Link>
                </div>
            </div>
        </section>

        <div class="absolute bottom-0 left-0 z-0 w-full overflow-hidden leading-[0]">
            <svg
                class="relative block h-48 w-full lg:h-64 xl:h-72"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 1440 320"
                preserveAspectRatio="none"
            >
                <!-- Wave 1 (fundo mais alto - vermelho escuro) -->
                <path
                    fill="#a33025"
                    fill-opacity="1"
                    d="M0,96L60,101.3C120,107,240,117,360,138.7C480,160,600,192,720,202.7C840,213,960,203,1080,186.7C1200,171,1320,149,1380,138.7L1440,128L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"
                ></path>

                <!-- Wave 2 (meio - vermelho médio) -->
                <path
                    fill="#7a231c"
                    fill-opacity="1"
                    d="M0,160L80,170.7C160,181,320,203,480,213.3C640,224,800,224,960,197.3C1120,171,1280,117,1360,90.7L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"
                ></path>

                <!-- Wave 3 (frente - bege claro) -->
                <path
                    fill="#f9f3ed"
                    fill-opacity="1"
                    d="M0,256L80,240C160,224,320,192,480,186.7C640,181,800,203,960,213.3C1120,224,1280,224,1360,213.3L1440,203L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"
                ></path>
            </svg>
        </div>

        <!-- Footer -->
        <footer class="relative z-10 pb-6 text-sm text-black/70">Made with ❤️ for Christmas – Secret Santa © {{ new Date().getFullYear() }}</footer>
    </div>
</template>

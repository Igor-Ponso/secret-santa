<script setup lang="ts">
// Image assets live in resources/assets, not under resources/js, so use the new @assets alias.
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import Bullet from '@/components/Bullet.vue';
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

    <div class="relative flex min-h-screen flex-col overflow-hidden bg-gradient-to-br from-red-800 via-red-600 to-red-500 text-white">
        <!-- Navbar -->
        <header class="relative z-20 flex w-full items-center justify-between gap-3 px-5 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-red-400 to-red-600 shadow-md ring-2 ring-white/15"
                >
                    <Gift class="h-6 w-6 text-white" />
                </div>
                <h1 class="text-xl font-bold tracking-wide text-white drop-shadow sm:text-2xl">
                    {{ t('landing.brand') }}
                </h1>
            </div>
            <div class="flex items-center gap-5">
                <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
                    <Link
                        v-if="page.props.auth?.user"
                        :href="route('dashboard')"
                        class="whitespace-nowrap rounded-full border border-white/30 bg-white/10 px-5 py-2 text-white/85 backdrop-blur transition hover:border-white/50 hover:bg-white/20 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                        >Dashboard</Link
                    >
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="whitespace-nowrap rounded-full border border-white/30 bg-white/10 px-5 py-2 text-white/85 backdrop-blur transition hover:border-white/50 hover:bg-white/20 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                        >
                            {{ t('landing.login') }}
                        </Link>
                        <Link
                            :href="route('register')"
                            class="whitespace-nowrap rounded-full bg-white px-5 py-2 font-semibold text-red-600 shadow shadow-black/20 ring-1 ring-white/40 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700/60 focus:ring-offset-2 focus:ring-offset-red-700"
                        >
                            {{ t('landing.register') }}
                        </Link>
                    </template>
                </nav>
                <LanguageSwitcher />
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative z-10 w-full flex-1">
            <div class="mx-auto grid h-full w-full max-w-7xl items-center gap-12 px-6 pb-24 pt-12 md:grid-cols-2 md:gap-16 lg:pt-20 xl:gap-24">
                <!-- Text -->
                <div class="max-w-xl space-y-7">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ t('landing.headline') }}
                    </h2>
                    <div class="h-1 w-24 rounded bg-red-300/90"></div>
                    <p class="text-xl leading-relaxed text-red-50/95 md:text-2xl">{{ t('landing.tagline') }}</p>
                    <p class="text-base leading-relaxed text-white/85 md:text-lg">{{ t('landing.pitch') }}</p>
                    <ul class="mt-5 space-y-3 text-base text-white/90 md:text-[17px]">
                        <li class="flex gap-2">
                            <Bullet class="mt-1" color="red-400" :size="8" :pulse="true" />
                            <span>{{ t('landing.benefit_secure', 'Secure & private by design') }}</span>
                        </li>
                        <li class="flex gap-2">
                            <Bullet class="mt-1" color="red-400" :size="8" :pulse="true" />
                            <span>{{ t('landing.benefit_draw', 'Fair anonymous draw algorithm') }}</span>
                        </li>
                        <li class="flex gap-2">
                            <Bullet class="mt-1" color="red-400" :size="8" :pulse="true" />
                            <span>{{ t('landing.benefit_wishlist', 'Smart wishlists & hints') }}</span>
                        </li>
                    </ul>
                    <div class="mt-12 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <Link
                            :href="route('register')"
                            class="group flex-1 rounded-2xl bg-white/95 px-9 py-4 text-center text-base font-semibold text-red-600 shadow-lg shadow-black/25 ring-1 ring-white/40 backdrop-blur transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-red-700/60 focus:ring-offset-2 focus:ring-offset-red-700 md:text-lg"
                        >
                            <span class="inline-flex items-center justify-center gap-2">{{ t('landing.cta_create') }} </span>
                        </Link>
                        <Link
                            :href="route('login')"
                            class="flex-1 rounded-2xl border border-white/50 bg-white/10 px-9 py-4 text-center text-base font-semibold text-white/90 underline shadow-inner backdrop-blur transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/60 focus:ring-offset-2 focus:ring-offset-red-700 md:text-lg"
                        >
                            {{ t('landing.cta_have_account') }}
                        </Link>
                    </div>
                </div>
                <!-- Mascot -->
                <div class="relative mx-auto flex max-w-md items-center justify-center md:max-w-none">
                    <div class="relative rounded-[38%] bg-white/50 p-5 shadow-2xl ring-1 ring-white/60 backdrop-blur-2xl">
                        <img
                            :src="mascotUrl"
                            alt="Santa Claus mascot"
                            class="w-full max-w-sm rotate-[-2deg] drop-shadow-[0_16px_28px_rgba(0,0,0,0.40)] transition-transform duration-500 ease-out hover:rotate-0 hover:scale-[1.03]"
                        />
                        <div class="pointer-events-none absolute -left-6 -top-6 h-24 w-24 animate-pulse rounded-full bg-red-300/30 blur-xl"></div>
                        <div
                            class="pointer-events-none absolute -bottom-6 -right-6 h-28 w-28 animate-pulse rounded-full bg-red-500/25 blur-2xl"
                        ></div>
                    </div>
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
        <footer class="relative z-20 pb-8 text-center font-medium text-black/80">
            {{ t('footer.credit', { year: new Date().getFullYear() }) }}
        </footer>
    </div>
</template>

<style scoped>
@keyframes pulseGradient {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>

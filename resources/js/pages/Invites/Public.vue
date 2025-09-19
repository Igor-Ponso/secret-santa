<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
// Import hero illustration via Vite alias so the asset path is resolved correctly in all environments.
import Bullet from '@/components/Bullet.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import inviteHero from '@assets/illustrations/invite-hero.png';

interface InvitationPageProps {
    invitation: {
        group: { id: number; name: string; description?: string | null } | null;
        inviter?: { id: number; name: string } | null;
        status: 'pending' | 'accepted' | 'declined' | 'revoked' | 'expired' | 'invalid' | 'share_link';
        expired: boolean;
        revoked?: boolean;
        token: string;
        authenticated?: boolean;
    };
}

const props = defineProps<InvitationPageProps>();
const { t } = useI18n();

const benefits = ['invites.public.benefits.gifting', 'invites.public.benefits.surprise', 'invites.public.benefits.memory'];
</script>

<template>
    <Head :title="t('invites.title')" />
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-red-800 via-red-600 to-red-500 text-white">
        <div class="absolute right-4 top-4 z-30"><LanguageSwitcher /></div>
        <div class="relative mx-auto flex w-full max-w-6xl flex-col items-center gap-10 px-6 py-12 md:flex-row md:items-stretch md:gap-16 lg:py-14">
            <!-- Illustration -->
            <div class="hidden w-full items-center justify-center md:flex md:w-1/2">
                <!-- Illustration without glass / blur effect -->
                <div class="relative">
                    <img
                        :src="inviteHero"
                        alt="Gift Invitation"
                        class="h-auto w-[460px] max-w-full drop-shadow-[0_10px_28px_rgba(0,0,0,0.50)] md:w-[500px] lg:w-[540px]"
                    />
                </div>
            </div>

            <!-- Content Card -->
            <div class="w-full max-w-lg md:w-1/2">
                <div
                    class="relative rounded-3xl border border-white/15 bg-white/5 p-8 shadow-[0_8px_32px_-8px_rgba(0,0,0,0.45)] ring-1 ring-white/25 backdrop-blur-xl before:pointer-events-none before:absolute before:inset-0 before:rounded-3xl before:bg-gradient-to-br before:from-white/20 before:via-white/5 before:to-transparent before:opacity-95 before:mix-blend-overlay md:p-12"
                >
                    <div class="space-y-5 text-center md:text-left">
                        <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white md:text-3xl lg:text-4xl">
                            {{ t('invites.public.heading') }}
                        </h1>
                        <div v-if="props.invitation.inviter && props.invitation.group" class="flex justify-center md:justify-start">
                            <div
                                class="inline-flex flex-wrap items-center gap-1.5 rounded-full px-4 py-2 font-bold tracking-wide text-white/90 md:gap-2 md:px-5"
                            >
                                <span class="inline-block rounded bg-green-600 px-2 py-0.5 font-semibold text-white shadow-sm backdrop-blur-sm">
                                    {{ props.invitation.inviter.name }}
                                </span>
                                <span class="opacity-80">{{ t('invites.public.invited_you_to_group', 'te convidou para o grupo') }}</span>
                                <span class="inline-block rounded bg-green-600 px-2 py-0.5 font-bold text-red-50 shadow-sm ring-1 ring-red-300/30">
                                    {{ props.invitation.group.name }}
                                </span>
                            </div>
                        </div>
                        <div class="mx-auto h-1 w-24 rounded bg-red-400/80 md:mx-0"></div>
                        <p class="text-lg leading-relaxed text-white/90 md:text-xl">{{ t('invites.public.subheading') }}</p>
                        <div v-if="props.invitation.status !== 'invalid'">
                            <div v-if="props.invitation.group" class="mt-4 space-y-2 text-sm md:text-base">
                                <p v-if="props.invitation.group.description" class="leading-relaxed text-white/75">
                                    {{ props.invitation.group.description }}
                                </p>
                            </div>
                            <p v-if="props.invitation.status === 'expired'" class="mt-4 text-xs font-medium text-yellow-200">
                                {{ t('invites.expired') }}
                            </p>
                            <p v-else-if="props.invitation.status === 'revoked'" class="mt-4 text-xs font-medium text-red-200">
                                {{ t('invites.revoked') }}
                            </p>
                            <p v-else-if="props.invitation.status === 'accepted'" class="mt-4 text-xs text-white/70">
                                {{ t('invites.already_accepted') }}
                            </p>
                            <p v-else-if="props.invitation.status === 'declined'" class="mt-4 text-xs text-white/70">
                                {{ t('invites.already_declined') }}
                            </p>
                            <template v-else>
                                <p v-if="props.invitation.status === 'share_link'" class="mt-2 text-sm font-medium text-white/80">
                                    {{
                                        t('invites.share_link_intro_public') ||
                                        'Este é um link público de participação. Crie ou acesse sua conta para pedir entrada.'
                                    }}
                                </p>
                                <ul class="mt-8 space-y-4 text-left">
                                    <li
                                        v-for="b in benefits"
                                        :key="b"
                                        class="flex items-start gap-3 text-base leading-snug text-white/95 md:text-[17px]"
                                    >
                                        <Bullet class="mt-3" color="green-400" :size="8" :pulse="true" />
                                        <span>{{ t(b) }}</span>
                                    </li>
                                </ul>
                                <div class="mt-6 flex flex-col gap-4 sm:flex-row">
                                    <a
                                        :href="route('login')"
                                        class="flex-1 rounded-xl bg-white px-7 py-3.5 text-center text-sm font-semibold text-red-600 shadow-lg transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700/60 focus:ring-offset-2 focus:ring-offset-red-700 md:text-base"
                                    >
                                        {{ t('auth.sign_in') }}
                                    </a>
                                    <a
                                        :href="route('register')"
                                        class="flex-1 rounded-xl border border-white/30 bg-white/10 px-7 py-3.5 text-center text-sm font-semibold text-white/90 shadow-inner transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/50 md:text-base"
                                    >
                                        {{ t('auth.register') }}
                                    </a>
                                </div>
                            </template>
                        </div>
                        <div v-else class="mt-4 rounded-md border border-red-300/40 bg-red-900/30 p-3 text-xs text-red-100/90">
                            {{ t('invites.invalid') }}
                        </div>
                    </div>
                </div>
                <p class="mt-10 text-center font-medium text-white/75 md:text-left">{{ t('footer.credit', { year: new Date().getFullYear() }) }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes snow {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 0 600px;
    }
}
</style>

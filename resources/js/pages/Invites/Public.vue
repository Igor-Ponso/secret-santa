<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
// Import hero illustration via Vite alias so the asset path is resolved correctly in all environments.
import inviteHero from '@assets/illustrations/invite-hero.svg';

interface InvitationPageProps {
    invitation: {
        group: { id: number; name: string; description?: string | null } | null;
        inviter?: { id: number; name: string } | null;
        status: 'pending' | 'accepted' | 'declined' | 'revoked' | 'expired' | 'invalid';
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
        <div class="relative mx-auto flex w-full max-w-6xl flex-col items-center gap-10 px-6 py-12 md:flex-row md:items-stretch md:gap-16 lg:py-14">
            <!-- Illustration -->
            <div class="flex w-full items-center justify-center md:w-1/2">
                <div class="relative rounded-3xl bg-white/10 p-6 shadow-2xl shadow-black/30 ring-1 ring-white/25 backdrop-blur-xl">
                    <img :src="inviteHero" alt="Presente Convite" class="h-auto w-[400px] max-w-full drop-shadow-[0_8px_24px_rgba(0,0,0,0.45)]" />
                    <div class="pointer-events-none absolute inset-0 rounded-3xl ring-1 ring-white/30"></div>
                </div>
            </div>

            <!-- Content Card -->
            <div class="w-full max-w-md md:w-1/2">
                <div class="bg-white/10/50 rounded-3xl border border-white/15 p-8 shadow-2xl ring-1 ring-white/20 backdrop-blur-2xl md:p-10">
                    <div class="space-y-4 text-center md:text-left">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/20 px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-white/90 shadow-sm backdrop-blur md:text-xs"
                        >
                            <span class="inline-block h-2 w-2 rounded-full bg-red-500 shadow-[0_0_0_3px_rgba(239,68,68,0.35)]"></span>
                            <span v-if="props.invitation.group" class="block text-base font-semibold tracking-wide text-red-200/90">
                                {{ props.invitation.group.name }}
                            </span>
                        </div>
                        <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white md:text-5xl lg:text-6xl">
                            {{ t('invites.public.heading') }}
                        </h1>
                        <div v-if="props.invitation.inviter" class="flex justify-center md:justify-start">
                            <div
                                class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-medium text-white/90 ring-1 ring-white/25 md:text-sm"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_0_3px_rgba(52,211,153,0.25)]"></span>
                                <span>{{ t('invites.public.invited_by', 'Convite enviado por') }} {{ props.invitation.inviter.name }}</span>
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
                                <ul class="mt-8 space-y-4 text-left">
                                    <li
                                        v-for="b in benefits"
                                        :key="b"
                                        class="flex items-start gap-3 text-base leading-snug text-white/95 md:text-[17px]"
                                    >
                                        <span class="mt-2 h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_3px_rgba(16,185,129,0.25)]"></span>
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
                <p class="mt-10 text-center font-medium text-white/75 md:text-left">Criado com ❤️ – Secret Santa © {{ new Date().getFullYear() }}</p>
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

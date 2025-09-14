<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface InvitationPageProps {
    invitation: {
        group: { id: number; name: string; description?: string | null } | null;
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
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-emerald-800 via-red-800 to-amber-600 text-white">
        <!-- Subtle pattern overlay -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_25%_25%,rgba(255,255,255,0.18),transparent_60%)]"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_75%_75%,rgba(255,255,255,0.15),transparent_65%)]"></div>
        <!-- Snow overlay -->
        <div class="pointer-events-none absolute inset-0 opacity-60 mix-blend-screen">
            <div
                class="bg-[url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'160\' height=\'160\' viewBox=\'0 0 160 160\'><g fill=\'%23ffffff33\'><circle cx=\'10\' cy=\'10\' r=\'2'/><circle cx=\'80\' cy=\'40\' r=\'1.5'/><circle cx=\'130\' cy=\'90\' r=\'2.2'/><circle cx=\'30\' cy=\'120\' r=\'1.8'/><circle cx=\'150\' cy=\'20\' r=\'1.4'/><circle cx=\'60\' cy=\'140\' r=\'2.4'/></g></svg>')] absolute inset-0 animate-[snow_18s_linear_infinite] bg-repeat"
            ></div>
            <div
                class="bg-[url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'220\' height=\'220\' viewBox=\'0 0 220 220\'><g fill=\'%23ffffff22\'><circle cx=\'40\' cy=\'30\' r=\'2.4'/><circle cx=\'120\' cy=\'80\' r=\'1.2'/><circle cx=\'200\' cy=\'160\' r=\'2'/><circle cx=\'90\' cy=\'200\' r=\'1.6'/><circle cx=\'10\' cy=\'180\' r=\'2.1'/></g></svg>')] absolute inset-0 animate-[snow_28s_linear_infinite_reverse] bg-repeat"
            ></div>
        </div>
        <!-- Top glow -->
        <div class="pointer-events-none absolute -top-32 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-white/20 blur-3xl"></div>

        <div class="relative mx-auto flex w-full max-w-6xl flex-col items-center gap-10 px-6 py-16 md:flex-row md:items-stretch md:gap-16">
            <!-- Illustration -->
            <div class="flex w-full items-center justify-center md:w-1/2">
                <div class="relative">
                    <img src="/build/assets/invite-hero.svg" alt="Invite Gift" class="h-auto w-[420px] max-w-full drop-shadow-2xl" />
                </div>
            </div>

            <!-- Content Card -->
            <div class="w-full max-w-md md:w-1/2">
                <div class="rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl ring-1 ring-white/10 backdrop-blur-xl">
                    <div class="space-y-4 text-center md:text-left">
                        <h1
                            class="bg-gradient-to-r from-amber-200 via-white to-emerald-100 bg-clip-text text-2xl font-bold leading-tight tracking-tight text-transparent drop-shadow-sm md:text-3xl"
                        >
                            {{ t('invites.public.heading') }}
                        </h1>
                        <p class="text-sm text-amber-100/90">{{ t('invites.public.subheading') }}</p>
                        <div v-if="props.invitation.status !== 'invalid'">
                            <p v-if="props.invitation.group" class="mt-4 text-sm">
                                <span class="font-semibold">{{ props.invitation.group.name }}</span>
                                <span v-if="props.invitation.group.description" class="text-white/70">
                                    — {{ props.invitation.group.description }}</span
                                >
                            </p>
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
                                <ul class="mt-6 space-y-2 text-left">
                                    <li v-for="b in benefits" :key="b" class="flex items-start gap-2 text-xs text-emerald-50/90">
                                        <span
                                            class="mt-0.5 inline-block h-2 w-2 flex-shrink-0 rounded-full bg-amber-300 shadow-[0_0_0_3px_rgba(253,230,138,0.25)]"
                                        ></span>
                                        <span class="[text-shadow:0_0_6px_rgba(0,0,0,0.2)]">{{ t(b) }}</span>
                                    </li>
                                </ul>
                                <p class="mt-6 text-xs text-emerald-50/90">{{ t('invites.login_to_accept') }}</p>
                                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                    <a
                                        :href="route('login')"
                                        class="flex-1 rounded-md bg-gradient-to-r from-amber-300 via-amber-200 to-emerald-200 px-5 py-2 text-center text-xs font-semibold text-emerald-900 shadow hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-amber-300/80 focus:ring-offset-2"
                                    >
                                        {{ t('auth.sign_in') }}
                                    </a>
                                    <a
                                        :href="route('register')"
                                        class="flex-1 rounded-md border border-emerald-200/40 bg-emerald-50/10 px-5 py-2 text-center text-xs font-semibold backdrop-blur hover:bg-emerald-50/20 focus:outline-none focus:ring-2 focus:ring-emerald-200/60"
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
                <p class="mt-6 text-center text-[11px] text-emerald-50/60 md:text-left">
                    {{ t('invites.public.footer_note') }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes snow {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(200px);
    }
}
</style>

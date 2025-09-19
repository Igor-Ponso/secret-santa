<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface GroupPublicPayload {
    name: string;
    description: string | null;
    participant_count: number;
    has_draw: boolean;
    draw_date: string | null;
    days_until_draw: number | null;
    currency: string | null;
    min_gift_cents: number | null;
    max_gift_cents: number | null;
}

const props = defineProps<{ group: GroupPublicPayload; code: string }>();

const giftRange = computed(() => {
    if (props.group.min_gift_cents == null && props.group.max_gift_cents == null) return null;
    const min = props.group.min_gift_cents != null ? (props.group.min_gift_cents / 100).toFixed(2) : null;
    const max = props.group.max_gift_cents != null ? (props.group.max_gift_cents / 100).toFixed(2) : null;
    if (min && max) return `${min} – ${max} ${props.group.currency || ''}`.trim();
    if (min) return `≥ ${min} ${props.group.currency || ''}`.trim();
    if (max) return `≤ ${max} ${props.group.currency || ''}`.trim();
    return null;
});

const drawMessage = computed(() => {
    if (props.group.has_draw) return 'Draw completed';
    if (props.group.days_until_draw == null) return 'Draw date not set';
    if (props.group.days_until_draw < 0) return 'Draw date passed (awaiting draw)';
    if (props.group.days_until_draw === 0) return 'Draw today';
    if (props.group.days_until_draw === 1) return '1 day until draw';
    return `${props.group.days_until_draw} days until draw`;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-950 text-slate-100">
        <Head :title="group.name" />
        <header class="flex items-center justify-between border-b border-slate-800 p-6">
            <h1 class="text-xl font-semibold tracking-tight">
                {{ group.name }}
            </h1>
            <Link :href="route('home')" class="text-sm text-slate-400 transition hover:text-slate-200">Home</Link>
        </header>
        <main class="mx-auto w-full max-w-3xl flex-1 p-6">
            <div class="space-y-6">
                <p v-if="group.description" class="leading-relaxed text-slate-300">{{ group.description }}</p>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Participants</div>
                        <div class="mt-1 text-2xl font-semibold">{{ group.participant_count }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Draw</div>
                        <div class="mt-1 text-sm font-medium" :class="group.has_draw ? 'text-emerald-400' : 'text-amber-300'">{{ drawMessage }}</div>
                        <div v-if="group.draw_date" class="mt-1 text-xs text-slate-500">{{ group.draw_date }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4" v-if="giftRange">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Gift Range</div>
                        <div class="mt-1 text-sm font-medium">{{ giftRange }}</div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-800 pt-4 sm:flex-row">
                    <Link
                        :href="route('register')"
                        class="rounded-md bg-rose-600 px-5 py-2.5 text-center text-sm font-medium transition hover:bg-rose-500"
                        >Create Account</Link
                    >
                    <Link
                        :href="route('login')"
                        class="rounded-md bg-slate-700 px-5 py-2.5 text-center text-sm font-medium transition hover:bg-slate-600"
                        >Login</Link
                    >
                </div>
                <p class="text-xs text-slate-500">Code: {{ code }}</p>
            </div>
        </main>
        <footer class="border-t border-slate-800 p-6 text-center text-xs text-slate-600">
            Secret Santa · Share this link with people you want to invite
        </footer>
    </div>
</template>

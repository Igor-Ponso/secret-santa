<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 text-slate-100"
    >
        <Head title="404" />
        <div class="max-w-md text-center">
            <h1 class="bg-gradient-to-r from-rose-400 to-fuchsia-500 bg-clip-text text-7xl font-extrabold tracking-tight text-transparent">404</h1>
            <p class="mt-4 text-xl font-semibold">Page not found</p>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                The page you are looking for may have been moved, deleted, or never existed. If you followed a link, it might be outdated.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <Link :href="route('home')" class="rounded-md bg-slate-700 px-5 py-2.5 text-sm font-medium transition hover:bg-slate-600"
                    >Go Home</Link
                >
                <Link :href="route('dashboard')" class="rounded-md bg-rose-600 px-5 py-2.5 text-sm font-medium transition hover:bg-rose-500"
                    >Dashboard</Link
                >
            </div>
            <div class="mt-10 text-xs uppercase tracking-wider text-slate-500">Secret Santa</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
// import { type NavItem } from '@/types'; // no longer directly used after reactive i18n refactor
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
// import { computed } from 'vue'; // not required yet (keeping static key list)

const { t } = useI18n();

// Store only translation keys so titles react when locale changes
interface SettingsNavItem {
    key: string;
    href: string;
}
const sidebarNavItems: SettingsNavItem[] = [
    { key: 'settings.nav_profile', href: '/settings/profile' },
    { key: 'settings.nav_password', href: '/settings/password' },
    { key: 'settings.nav_appearance', href: '/settings/appearance' },
    { key: 'settings.nav_language', href: '/settings/language' },
];

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading :title="t('settings.breadcrumb_settings')" :description="t('settings.manage_account', 'Manage your profile and account settings')" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-x-0 space-y-1">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ t(item.key) }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

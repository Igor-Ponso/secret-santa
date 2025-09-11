import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    profile_photo_url?: string | null;
}

declare module '@inertiajs/vue3' {
    export function usePage<TPageProps = SharedData>(): Page<TPageProps>;
  }

export type BreadcrumbItemType = BreadcrumbItem;

// Allow importing SVGs as URLs (handled by Vite)
declare module '*.svg' {
    const src: string;
    export default src;
}

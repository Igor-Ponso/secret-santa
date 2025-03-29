<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { computed } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    profile_photo: null as File | null,
});
const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.profile_photo = target.files[0];
    }
};

const removeAvatar = () => {
    form.profile_photo = null;
    user.profile_photo_url = null;
};

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const avatarSrc = computed(() => {
    // se o usuário fez upload de um novo avatar
    if (form.profile_photo instanceof File) {
        return URL.createObjectURL(form.profile_photo);
    }

    // se o usuário clicou em "remover"
    if (form.profile_photo === null) {
        return null;
    }

    // valor inicial do backend
    return user.profile_photo_url;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />
        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name, email and avatar" />
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Avatar Upload -->
                    <div class="flex items-center gap-4">
                        <div class="group relative">
                            <label for="avatar-upload" class="cursor-pointer">
                                <Avatar size="lg">
                                    <AvatarImage :src="avatarSrc ? avatarSrc : user.profile_photo_url" alt="User avatar" />
                                    <AvatarFallback>{{ user.name[0] }} </AvatarFallback>
                                </Avatar>
                            </label>

                            <input id="avatar-upload" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />

                            <!-- Remove Button -->
                            <button
                                v-if="user.profile_photo_url || avatarSrc"
                                type="button"
                                @click="removeAvatar"
                                class="absolute right-0 top-0 rounded-full opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                ✖
                            </button>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">Click the avatar to upload a new photo.</p>
                            <p class="text-sm text-muted-foreground">Max size: 2MB</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.profile_photo" class="mt-1" />

                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" v-model="form.email" required autocomplete="username" placeholder="Email address" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Email Verification -->
                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

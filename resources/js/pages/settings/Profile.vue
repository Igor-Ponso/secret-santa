<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';

import { type BreadcrumbItem, type SharedData, type User } from '@/types';

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

const avatarPreview = ref<string | null>(null);

const form = useForm({
    name: user.name,
    email: user.email,
    profile_photo: null as File | null,
});

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.profile_photo = target.files[0];
        avatarPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};
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
                        <Avatar size="lg">
                            <AvatarImage :src="avatarPreview || user.profile_photo_url || ''" alt="User avatar" />
                            <AvatarFallback>{{ user.name[0] }}</AvatarFallback>
                        </Avatar>

                        <input
                            type="file"
                            accept="image/*"
                            class="block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-neutral-100 file:text-neutral-800 hover:file:bg-neutral-200"
                            @change="handleAvatarChange"
                        />
                        <InputError :message="form.errors.profile_photo" class="mt-1" />
                    </div>

                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
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

<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface InvitationPageProps {
    invitation: {
        group: { id: number; name: string; description?: string | null };
        email: string;
        status: 'pending' | 'accepted' | 'declined';
        expired: boolean;
        token: string;
    };
}

const props = defineProps<InvitationPageProps>();
const { t } = useI18n();

function accept() {
    router.post(route('invites.accept', props.invitation.token));
}

function decline() {
    router.post(route('invites.decline', props.invitation.token));
}
</script>

<template>
    <Head :title="t('invites.title')" />
    <AppLayout>
        <div class="mx-auto max-w-md space-y-6 p-6">
            <h1 class="text-xl font-semibold tracking-tight">{{ t('invites.group_invitation') }}</h1>
            <div class="rounded-md border p-4 text-sm leading-relaxed">
                <p>
                    {{ t('invites.accept_desc').split('.')[0] }}
                    <span class="font-medium">{{ props.invitation.group.name }}</span>
                    <span v-if="props.invitation.group.description" class="text-muted-foreground"> — {{ props.invitation.group.description }}</span>
                </p>
                <p class="mt-2 text-xs text-muted-foreground">{{ t('invites.invitation_for') }} {{ props.invitation.email }}</p>
                <p v-if="props.invitation.expired" class="mt-2 text-xs font-medium text-destructive">{{ t('invites.expired') }}</p>
                <p v-else-if="props.invitation.status !== 'pending'" class="mt-2 text-xs text-muted-foreground">
                    {{ t('invites.already_status').replace(':status', props.invitation.status) }}
                </p>
            </div>
            <div class="flex gap-3" v-if="!props.invitation.expired && props.invitation.status === 'pending'">
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <button class="rounded-md bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90">
                            {{ t('invites.accept') }}
                        </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>{{ t('invites.accept_question') }}</AlertDialogTitle>
                            <AlertDialogDescription class="text-xs">
                                {{ t('invites.accept_desc') }}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel class="text-xs">{{ t('common.actions.cancel') }}</AlertDialogCancel>
                            <AlertDialogAction @click="accept" class="text-xs">{{ t('invites.accept') }}</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <button class="rounded-md border px-4 py-2 text-xs hover:bg-accent">{{ t('invites.decline') }}</button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>{{ t('invites.decline_question') }}</AlertDialogTitle>
                            <AlertDialogDescription class="text-xs">
                                {{ t('invites.decline_desc') }}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel class="text-xs">{{ t('common.actions.cancel') }}</AlertDialogCancel>
                            <AlertDialogAction @click="decline" class="text-xs">{{ t('invites.decline') }}</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>
    </AppLayout>
</template>

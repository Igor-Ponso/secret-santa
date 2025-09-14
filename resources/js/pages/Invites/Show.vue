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
        group: { id: number; name: string; description?: string | null } | null;
        email: string | null; // only present if authenticated user matches invite
        status: 'pending' | 'accepted' | 'declined' | 'revoked' | 'expired' | 'invalid';
        expired: boolean;
        revoked?: boolean;
        token: string;
        can_accept?: boolean;
    };
}

const props = defineProps<InvitationPageProps>();
const { t } = useI18n();

const accept = () => {
    if (props.invitation.status !== 'pending') return;
    router.post(route('invites.accept', props.invitation.token));
};

const decline = () => {
    if (props.invitation.status !== 'pending') return;
    router.post(route('invites.decline', props.invitation.token));
};
</script>

<template>
    <Head :title="t('invites.title')" />
    <AppLayout>
        <div class="mx-auto max-w-md space-y-6 p-6">
            <h1 class="text-xl font-semibold tracking-tight">{{ t('invites.group_invitation') }}</h1>
            <div class="rounded-md border p-4 text-sm leading-relaxed" v-if="props.invitation.status !== 'invalid'">
                <p>
                    <template v-if="props.invitation.group">
                        <span class="font-medium">{{ props.invitation.group.name }}</span>
                        <span v-if="props.invitation.group.description" class="text-muted-foreground">
                            — {{ props.invitation.group.description }}</span
                        >
                    </template>
                </p>
                <p class="mt-2 text-xs text-muted-foreground" v-if="props.invitation.email">{{ t('invites.invitation_for_you') }}</p>
                <p v-if="props.invitation.status === 'expired'" class="mt-2 text-xs font-medium text-destructive">{{ t('invites.expired') }}</p>
                <p v-else-if="props.invitation.status === 'revoked'" class="mt-2 text-xs font-medium text-destructive">{{ t('invites.revoked') }}</p>
                <p v-else-if="props.invitation.status === 'accepted'" class="mt-2 text-xs text-muted-foreground">
                    {{ t('invites.already_accepted') }}
                </p>
                <p v-else-if="props.invitation.status === 'declined'" class="mt-2 text-xs text-muted-foreground">
                    {{ t('invites.already_declined') }}
                </p>
            </div>
            <div v-else class="rounded-md border p-4 text-sm text-destructive">{{ t('invites.invalid') }}</div>
            <div class="flex gap-3" v-if="props.invitation.status === 'pending' && props.invitation.can_accept">
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

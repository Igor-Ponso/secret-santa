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
import { errorToast } from '@/lib/notifications';
import { Head, router } from '@inertiajs/vue3';

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

function accept() {
    router.post(route('invites.accept', props.invitation.token), {}, { onError: () => errorToast('Failed to accept invitation') });
}

function decline() {
    router.post(route('invites.decline', props.invitation.token), {}, { onError: () => errorToast('Failed to decline invitation') });
}
</script>

<template>
    <Head title="Invitation" />
    <AppLayout>
        <div class="mx-auto max-w-md space-y-6 p-6">
            <h1 class="text-xl font-semibold tracking-tight">Group Invitation</h1>
            <div class="rounded-md border p-4 text-sm leading-relaxed">
                <p>
                    You've been invited to join <span class="font-medium">{{ props.invitation.group.name }}</span>
                    <span v-if="props.invitation.group.description" class="text-muted-foreground"> — {{ props.invitation.group.description }}</span>
                </p>
                <p class="mt-2 text-xs text-muted-foreground">Invitation for: {{ props.invitation.email }}</p>
                <p v-if="props.invitation.expired" class="mt-2 text-xs font-medium text-destructive">This invitation has expired.</p>
                <p v-else-if="props.invitation.status !== 'pending'" class="mt-2 text-xs text-muted-foreground">
                    Already {{ props.invitation.status }}.
                </p>
            </div>
            <div class="flex gap-3" v-if="!props.invitation.expired && props.invitation.status === 'pending'">
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <button class="rounded-md bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90">
                            Accept
                        </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Accept invitation?</AlertDialogTitle>
                            <AlertDialogDescription class="text-xs">
                                You will join this group and the owner will see you as participante. Continue?
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel class="text-xs">Cancel</AlertDialogCancel>
                            <AlertDialogAction @click="accept" class="text-xs">Accept</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <button class="rounded-md border px-4 py-2 text-xs hover:bg-accent">Decline</button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Decline invitation?</AlertDialogTitle>
                            <AlertDialogDescription class="text-xs">
                                You can only accept while it is valid. Declining means you won't participate unless reinvited.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel class="text-xs">Cancel</AlertDialogCancel>
                            <AlertDialogAction @click="decline" class="text-xs">Decline</AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>
    </AppLayout>
</template>

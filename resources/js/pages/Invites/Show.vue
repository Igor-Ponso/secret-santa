<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { errorToast } from '@/lib/notifications';

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
        <p v-else-if="props.invitation.status !== 'pending'" class="mt-2 text-xs text-muted-foreground">Already {{ props.invitation.status }}.</p>
      </div>
      <div class="flex gap-3" v-if="!props.invitation.expired && props.invitation.status === 'pending'">
        <button @click="accept" class="rounded-md bg-primary px-4 py-2 text-xs font-medium text-primary-foreground hover:bg-primary/90">Accept</button>
        <button @click="decline" class="rounded-md border px-4 py-2 text-xs hover:bg-accent">Decline</button>
      </div>
    </div>
  </AppLayout>
 </template>

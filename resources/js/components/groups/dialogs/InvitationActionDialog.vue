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
} from '@/components/ui/alert-dialog';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

interface Props {
    mode: 'resend' | 'revoke' | null;
    open: boolean;
    actingOn: boolean;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
const { t } = useI18n();
</script>

<template>
    <AlertDialog
        v-if="mode"
        :open="open"
        @update:open="
            (v: any) => {
                if (!v) emit('close');
            }
        "
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    {{
                        mode === 'revoke'
                            ? t('groups.revoke_invite_title') || 'Revogar convite?'
                            : t('groups.resend_invite_title') || 'Reenviar convite?'
                    }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-xs">
                    <template v-if="mode === 'revoke'">
                        {{
                            t('groups.revoke_invite_desc') ||
                            'Essa ação impedirá que o convidado aceite o convite existente. Você poderá criar outro depois.'
                        }}
                    </template>
                    <template v-else>
                        {{
                            t('groups.resend_invite_desc') ||
                            'Um novo token será gerado e o anterior se torna inválido. Garanta que vai reenviar o link atualizado por e-mail.'
                        }}
                    </template>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="emit('close')" class="text-xs">{{ t('groups.cancel') }}</AlertDialogCancel>
                <AlertDialogAction @click="emit('confirm')" :disabled="actingOn" class="flex items-center gap-2 text-xs">
                    <LoaderCircle v-if="actingOn" class="h-4 w-4 animate-spin" />
                    {{ mode === 'revoke' ? t('groups.revoke') || 'Revogar' : t('groups.resend') || 'Reenviar' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

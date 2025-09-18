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
    open: boolean;
    busy: boolean;
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
        :open="open"
        @update:open="
            (v: any) => {
                if (!v) emit('close');
            }
        "
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ t('groups.confirm_remove_title') }}</AlertDialogTitle>
                <AlertDialogDescription class="text-sm">
                    {{ t('groups.confirm_remove_desc') }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="busy">{{ t('groups.cancel') }}</AlertDialogCancel>
                <AlertDialogAction
                    @click="emit('confirm')"
                    :disabled="busy"
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                >
                    <LoaderCircle v-if="busy" class="h-4 w-4 animate-spin" />
                    {{ t('groups.confirm') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

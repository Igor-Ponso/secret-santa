import type { PendingAction } from '@/interfaces/twofactor';
import { computed } from 'vue';

const ACTION_MAP: Record<string, string> = {
    enable_2fa: 'Enable Two-Factor Authentication',
    disable_2fa: 'Disable Two-Factor Authentication',
    revoke_all: 'Revoke all trusted devices',
    revoke_one: 'Revoke a trusted device',
    rename: 'Rename a trusted device',
};

export const usePendingActionLabel = (getAction: () => PendingAction | null | undefined) => {
    const pendingActionLabel = computed(() => {
        const type = getAction()?.type;
        if (!type) return '';
        return ACTION_MAP[type] || '';
    });
    return { pendingActionLabel };
};

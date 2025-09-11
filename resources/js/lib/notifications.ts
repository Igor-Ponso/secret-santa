import { useToast } from 'vue-toastification';

export type ToastKind = 'success' | 'error' | 'info' | 'warning';

interface NotifyOptions {
    description?: string;
    duration?: number;
    // future: action buttons, ids etc
}

function base(kind: ToastKind, message: string, opts: NotifyOptions = {}) {
    const t = useToast();
    const { description, duration } = opts;
    const body = description ? `${message}\n${description}` : message;
    t[kind](body, { timeout: duration ?? 3500 });
}

export const notify = {
    success: (m: string, o?: NotifyOptions) => base('success', m, o),
    error: (m: string, o?: NotifyOptions) => base('error', m, o),
    info: (m: string, o?: NotifyOptions) => base('info', m, o),
    warning: (m: string, o?: NotifyOptions) => base('warning', m, o),
};

// Convenience one-liners
export const successToast = notify.success;
export const errorToast = notify.error;
export const infoToast = notify.info;
export const warningToast = notify.warning;

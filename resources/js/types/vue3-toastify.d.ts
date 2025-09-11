declare module 'vue3-toastify' {
    import type { Plugin } from 'vue';
    export interface ToastOptions {
        autoClose?: number | false;
        position?: string;
        theme?: 'light' | 'dark' | 'system';
        type?: 'default' | 'success' | 'info' | 'warning' | 'error';
        pauseOnHover?: boolean;
        pauseOnFocusLoss?: boolean;
        hideProgressBar?: boolean;
        newestOnTop?: boolean;
        closeOnClick?: boolean;
        transition?: string;
    }
    export type ToastContainerOptions = ToastOptions;
    type ToastFn = (msg: string, options?: ToastOptions) => void;
    export interface ToastApi {
        success: ToastFn;
        error: ToastFn;
        info: ToastFn;
        warning: ToastFn;
        (msg: string, options?: ToastOptions): void;
    }
    const plugin: Plugin;
    export const toast: ToastApi;
    export default plugin;
}

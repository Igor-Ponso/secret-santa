import type { SharedData } from '@/types';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';
// Omit direct Page import to avoid editor resolution issues; use any and rely on runtime shape.

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page: any) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        // Use eager glob so components are synchronous (avoids Promise<...> type issue)
        resolve: (name) => {
            const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
            const mod = pages[`./pages/${name}.vue`] as { default: DefineComponent } | undefined;
            if (!mod) {
                throw new Error(`Page not found: ${name}`);
            }
            return mod.default;
        },
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) });

            // Safely extract ziggy config (typed as unknown in page.props)
            const rawZiggy = (page.props as Partial<SharedData>).ziggy as { location: string } | undefined;
            const ziggyConfig = rawZiggy ? { ...rawZiggy, location: new URL(rawZiggy.location) } : { location: new URL('http://localhost') };

            // Wrap ziggyRoute preserving full signature; fallback config if omitted
            const route = ((name?: any, params?: any, absolute?: boolean, config?: any) =>
                ziggyRoute(name, params, absolute, config ?? ziggyConfig)) as typeof ziggyRoute;

            app.config.globalProperties.route = route as any;

            if (typeof window === 'undefined') {
                (globalThis as any).route = route;
            }

            app.use(plugin);

            return app;
        },
    }),
);

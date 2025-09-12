import { createI18n } from 'vue-i18n';

// Dynamic loader for translation JSON split by locale and domain.
// Convention: resources/js/languages/{locale}/**/*.json
// locale folders: en, pt_BR, fr

function loadLocaleMessages() {
    const messages: Record<string, any> = {};
    const modules = import.meta.glob('./languages/**/*.json', { eager: true });
    Object.entries(modules).forEach(([path, mod]) => {
        const match = /\.\/languages\/([A-Za-z_]+)\/(.*)\.json$/.exec(path);
        if (!match) return;
        let locale = match[1];
        if (locale === 'ptBR') locale = 'pt_BR'; // legacy folder name support
        // const segment = match[2].replace(/\\/g, '/'); // reserved for future nested namespace handling
        messages[locale] = messages[locale] || {};
        // Merge file content into locale root (namespaces inside JSON control grouping)
        Object.assign(messages[locale], (mod as any).default || {});
    });
    return messages;
}

const messages = loadLocaleMessages();

function readCookieLocale(): string {
    const raw = document.cookie.match(/(?:^|; )locale=([^;]+)/)?.[1];
    if (!raw) return 'pt_BR';
    // Accept explicit 'pt_BR' or 'pt-BR'; keep 'en' and 'fr' as-is.
    if (/^pt([-_]?BR)?$/i.test(raw)) return 'pt_BR';
    return raw;
}

export const i18n = createI18n({
    legacy: false,
    locale: readCookieLocale(),
    fallbackLocale: 'en',
    messages,
});

// Optional: observe cookie changes via visibility change (simple cheap re-sync)
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        const newLocale = readCookieLocale();
        if (i18n.global.locale.value !== newLocale) {
            i18n.global.locale.value = newLocale;
        }
    }
});

export type AppI18n = typeof i18n;

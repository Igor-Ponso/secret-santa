// Simple i18n utility reading server-provided language (assumes injected lang code via window.Laravel.locale)
// Fallback to 'pt' then 'en'.

type Dict = Record<string, any>;

// These will be replaced at build via Vite require semantics if desired;
// for now we rely on backend exposing needed subset or we can inline dynamic import.
// Since current language files are PHP (server-side), for front-end we'll replicate needed keys via a build step later.

const runtimeDict: Record<string, Dict> = {
    en: {
        groups: {
            participants: 'Participants',
            invitations: 'Invitations',
            join_requests: 'Join Requests',
            draw: 'Draw',
            owner: 'Owner',
            you: 'You',
            remove: 'Remove',
            transfer: 'Transfer',
            activities: 'Recent Activity',
            confirm: 'Confirm',
            cancel: 'Cancel',
            no_participants: 'No participants yet.',
            no_invites: 'No invitations.',
            no_join_requests: 'No requests.',
            confirm_remove_title: 'Remove participant?',
            confirm_remove_desc: 'This action revokes participation. User can be re-invited later.',
            confirm_transfer_title: 'Transfer ownership?',
            confirm_transfer_desc: 'After transferring you lose owner privileges.',
            no_results: 'No results for ":query".',
        },
        // onboarding keys now moved to JSON locale file
    },
    pt: {},
};

// We'll fetch the PHP-provided translation sets later; for now rely on PT markup already in templates.

export function t(path: string, params: Record<string, string | number> = {}): string {
    const locale = (window as any).Laravel?.locale || 'pt';
    const server = (window as any).Laravel?.translations || {};
    const dict = { ...(runtimeDict[locale] || {}), ...server };
    const parts = path.split('.');
    let cur: any = dict;
    for (const p of parts) {
        if (cur && typeof cur === 'object' && p in cur) cur = cur[p];
        else return path; // fallback to key
    }
    if (typeof cur !== 'string') return path;
    return Object.keys(params).reduce((acc, k) => acc.replace(new RegExp(':' + k, 'g'), String(params[k])), cur);
}

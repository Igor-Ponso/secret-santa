// Centralized group-related TypeScript interfaces
// Extracted from various Vue SFCs for reuse and consistency.

export interface Recipient {
    id: number;
    name: string;
}

export interface WishlistItem {
    id: number;
    item: string;
    url?: string | null;
    note?: string | null;
}

export interface GroupMetrics {
    pending: number;
    accepted: number;
    declined: number;
    revoked?: number;
    participants?: number;
    invited?: number;
    min_participants_met?: boolean;
    wishlist_coverage_percent?: number;
    ready_for_draw?: boolean;
    readiness_threshold?: number; // added to align with backend exposure
}

export interface GroupShowProps {
    group: {
        id: number;
        name: string;
        description?: string | null;
        is_owner: boolean;
        participants?: any[];
        invitations?: any[];
        join_requests?: any[];
        invitations_meta?: any;
        join_requests_meta?: any;
        metrics?: GroupMetrics;
        initial_tab?: 'participants' | 'invitations' | 'join_requests';
        [key: string]: any; // allow forward compatibility
    };
}

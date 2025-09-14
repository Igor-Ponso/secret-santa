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
}

export interface GroupShowProps {
    group: any; // TODO: tighten typing incrementally (participants, invitations, etc.)
}

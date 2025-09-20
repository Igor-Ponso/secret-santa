// Security & Two-Factor related shared interfaces
export interface TrustedDevice {
    id: number;
    name: string | null;
    last_used_at: string;
    created_at: string;
    ip_address?: string | null;
    os?: string | null;
    browser?: string | null;
    user_agent?: string | null;
    current?: boolean;
    latitude?: number | null;
    longitude?: number | null;
}

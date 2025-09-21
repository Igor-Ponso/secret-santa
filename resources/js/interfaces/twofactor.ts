// Two-Factor Authentication related shared interfaces

export interface PendingAction {
    type?: string;
    id?: string | number | null;
    name?: string | null;
}

export interface TwoFactorChallengeProps {
    mode?: string;
    resent?: boolean;
    expires_at?: string;
    remaining_seconds?: number;
    server_time?: string;
    resend_allowed?: boolean;
    resend_wait_seconds?: number;
    resend_suspended?: boolean;
    resend_min_interval?: number;
    next_resend_at?: string | null;
    resend_attempt_count?: number;
    resend_max_before_suspend?: number;
    pending_action?: PendingAction | null;
}

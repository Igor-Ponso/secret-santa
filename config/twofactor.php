<?php

return [
    // Time to live (seconds) for a challenge code (reduced from default 300 to 180 for UX clarity).
    'code_ttl' => 180,

    // Length of the numeric / alphanumeric code (service currently emits numeric of this length).
    'code_length' => 6,

    // Maximum attempts before challenge invalidation (not yet strictly enforced everywhere).
    'max_attempts' => 5,

    // Whether to queue outgoing code emails (false keeps behaviour synchronous for now).
    'use_queue' => false,

    // Escalating resend backoff (seconds) keyed by the nth resend action performed.
    // First resend (count=1) applies delay before the NEXT resend.
    'resend_backoff' => [
        1 => 120,   // after first resend, wait 2m for second
        2 => 300,   // after second resend, wait 5m
        3 => 600,   // 10m
        4 => 900,   // 15m
        5 => 1800,  // 30m
        6 => 3600,  // 60m
    ],

    // After this many resends (exceeding keys above) the account is suspended for 2FA and a password reset mail is triggered.
    'max_resends_before_suspend' => 7,
    // Suspension message (can later move to lang files)
    'suspension_reason' => 'For security, 2FA code resends have been temporarily disabled. Please reset your password via the link we just sent to your email.',

    // Minimal interval (seconds) guard using Laravel RateLimiter to avoid rapid-fire bursts within the same second
    'min_resend_interval' => 5,
];

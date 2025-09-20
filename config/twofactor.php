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
];

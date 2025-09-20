<?php

return [
    'code_length' => env('TWO_FACTOR_CODE_LENGTH', 6),
    'code_ttl' => env('TWO_FACTOR_CODE_TTL', 300), // seconds
    'max_attempts' => env('TWO_FACTOR_MAX_ATTEMPTS', 5),
    'trusted_device_ttl_days' => env('TWO_FACTOR_TRUSTED_TTL_DAYS', 90),
    // If true, emails are queued (needs queue worker). If false, they are sent immediately.
    'use_queue' => env('TWO_FACTOR_USE_QUEUE', false),
];

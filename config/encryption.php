<?php

return [
    // Version applied as prefix (e.g., v1:<cipher>) for assignment receiver encryption.
    'assignments_version' => env('ASSIGNMENTS_CIPHER_VERSION', 1),
    // Enable/disable scheduled verification job (assignments:verify-ciphers)
    'verify_schedule_enabled' => env('ASSIGNMENTS_VERIFY_SCHEDULE_ENABLED', true),
];

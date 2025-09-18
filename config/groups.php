<?php

return [
    // Percentage (0-100) of participants with at least one wishlist item to consider group "ready".
    'readiness_wishlist_threshold' => (int) env('GROUP_READINESS_WISHLIST_THRESHOLD', 50),
];

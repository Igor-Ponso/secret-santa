<?php
/**
 * Central test credentials (non-sensitive placeholders).
 * Keeping a single source reduces false positives in secret scanners.
 */

if (!defined('TEST_PASSWORD')) {
    define('TEST_PASSWORD', 'dummy-test-password-1');
}

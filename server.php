<?php
/**
 * server.php
 * --------------------------------------------------------------------------
 * Router script for PHP's built-in server ("php -S").
 * We add this so that on platforms where the buildpack doesn't automatically
 * set the document root to /public (e.g., current Koyeb config w/out custom
 * Apache or nginx), we can still serve the Laravel app correctly.
 *
 * HOW IT WORKS
 * 1. The Procfile (or Run Command) launches: php -S 0.0.0.0:$PORT server.php
 * 2. Each incoming request first comes here.
 * 3. If the request matches a real file inside public/ (JS, CSS, images,
 *    built assets), we return false so the built-in server serves it directly.
 * 4. Otherwise we require public/index.php (Laravel front controller).
 *
 * This replicates the historical Laravel pattern when using the dev server.
 * It's perfectly acceptable for small scale/staging. For higher traffic,
 * consider a proper web server (nginx/Apache/FrankenPHP) with /public docroot.
 * --------------------------------------------------------------------------
 */

$publicPath = __DIR__ . '/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$requested = $publicPath . $uri;

// Serve static file if it exists (letting php -S handle it faster)
if ($uri !== '/' && file_exists($requested) && !is_dir($requested)) {
    return false;
}

// Fallback: bootstrap Laravel
require $publicPath . '/index.php';

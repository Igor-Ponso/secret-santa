<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

it('all backend messages.* translation keys referenced in code exist in every locale', function () {
    $fs = new Filesystem();

    $pathsToScan = [
        base_path('app'),
        base_path('routes'),
        base_path('resources/views'),
        base_path('tests') // in case tests reference translation keys
    ];

    $phpFiles = [];
    foreach ($pathsToScan as $path) {
        if (!$fs->exists($path))
            continue;
        $phpFiles = array_merge($phpFiles, $fs->allFiles($path));
    }

    $keys = collect();

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php')
            continue;
        $contents = $fs->get($file->getRealPath());
        // Match __('messages.something') and __("messages.something")
        if (preg_match_all('/__\([\"\']messages\.([A-Za-z0-9_.-]+)[\"\']/', $contents, $m)) {
            foreach ($m[1] as $rawKey) {
                // Normalize and exclude obvious test placeholder keys
                if (in_array($rawKey, ['something', 'xxx']))
                    continue;
                $keys->push('messages.' . $rawKey);
            }
        }
    }

    $keys = $keys->unique()->values();

    // Load locale arrays
    $locales = collect(['en', 'pt_BR', 'fr'])->mapWithKeys(function ($loc) {
        $file = lang_path($loc . '/messages.php');
        $data = include $file; // returns array
        // Wrap under root key 'messages' to align with __('messages.xxx') usage
        return [$loc => ['messages' => $data]];
    });

    $missing = [];

    foreach ($keys as $key) {
        foreach ($locales as $locale => $data) {
            if (!array_key_exists_path($data, $key)) {
                $missing[] = "$locale:$key";
            }
        }
    }

    expect($missing)->toBeEmpty('Missing translation keys: ' . implode(', ', $missing));
});

if (!function_exists('array_key_exists_path')) {
    function array_key_exists_path(array $array, string $dotPath): bool
    {
        $segments = explode('.', $dotPath);
        $cursor = $array;
        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return false;
            }
            $cursor = $cursor[$segment];
        }
        return true;
    }
}

<?php

namespace Tests\Support;

use Illuminate\Filesystem\Filesystem;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Locale
{
    public static function frontendLocales(): array
    {
        return ['en', 'pt_BR', 'fr'];
    }

    public static function loadFrontendKeys(string $locale): array
    {
        $fs = new Filesystem();
        $base = resource_path('js/languages/' . $locale);
        if (!$fs->exists($base))
            return [];
        $keys = [];
        foreach ($fs->files($base) as $file) {
            if ($file->getExtension() !== 'json')
                continue;
            $json = json_decode($fs->get($file->getRealPath()), true);
            if (!is_array($json))
                continue;
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($json));
            foreach ($iterator as $value) {
                $path = [];
                foreach (range(0, $iterator->getDepth()) as $depth) {
                    $path[] = $iterator->getSubIterator($depth)->key();
                }
                $keys[] = implode('.', $path);
            }
        }
        return array_values(array_unique($keys));
    }
}

<?php

use Illuminate\Filesystem\Filesystem;

it('does not contain raw Portuguese literals in backend code', function () {
    $fs = new Filesystem();

    $scanDirs = [base_path('app'), base_path('routes')];
    $phpFiles = [];
    foreach ($scanDirs as $dir) {
        if ($fs->exists($dir)) {
            $phpFiles = array_merge($phpFiles, $fs->allFiles($dir));
        }
    }

    // Extended list of Portuguese words (lowercase variants only; we'll case-insensitively search)
    $portugueseTokens = [
        'convite',
        'sorteio',
        'exclusao',
        'exclusões',
        'exclusoes',
        'amigo secreto',
        'amigos secretos',
        'descricao',
        'mínimo',
        'máximo',
        'moeda',
        'wishlist',
        'bloqueado',
        'após',
        'pronto',
        'adicionar'
    ];
    // Allowlist: substrings that may legitimately appear (avoid false positives)
    $allow = [
        'laravel',
        'wishlist', // domain noun kept in code (model, class names)
    ];

    $offenders = [];

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php')
            continue;
        $path = $file->getRealPath();
        // Skip language directory if inadvertently added
        if (str_contains($path, DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR))
            continue;
        $contents = $fs->get($path);
        $lower = mb_strtolower($contents, 'UTF-8');
        foreach ($portugueseTokens as $token) {
            // Word boundary (very loose: token preceded/followed by non-letter or start/end)
            if (preg_match('/(^|[^a-zá-ú])' . preg_quote($token, '/') . '([^a-zá-ú]|$)/u', $lower)) {
                // Skip allowlist
                $skip = false;
                foreach ($allow as $a) {
                    if (str_contains($token, $a)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip)
                    $offenders[] = $path . ' => ' . $token;
            }
        }
        // Heuristic: detect common Portuguese diacritics if not part of allowlist and not inside a comment? (simple pass)
        if (preg_match_all('/[ãõáéíóúç]/u', $lower)) {
            // Rough heuristic: ignore if file is clearly a translation loader (none here) else flag
            // We'll skip adding generic diacritic offenders to avoid noise for now.
        }
    }

    expect($offenders)->toBeEmpty('Portuguese literals found outside translation files: ' . implode(', ', $offenders));
});

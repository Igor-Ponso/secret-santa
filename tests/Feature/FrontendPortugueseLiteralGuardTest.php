<?php

use Illuminate\Filesystem\Filesystem;

it('does not contain raw Portuguese literals in frontend source', function () {
    $fs = new Filesystem();
    $base = resource_path('js');
    if (!$fs->exists($base)) {
        $this->markTestSkipped('No frontend resources found');
    }

    $extensions = ['js', 'ts', 'vue'];
    $files = collect($fs->allFiles($base))
        ->filter(fn($f) => in_array($f->getExtension(), $extensions));

    $tokens = [
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
        'bloqueado',
        'após',
        'pronto',
        'adicionar'
    ];
    $allow = [
        'wishlist'
    ];

    $offenders = [];

    foreach ($files as $file) {
        $path = $file->getRealPath();
        // Skip locale JSON imports indirectly by path pattern
        if (str_contains($path, 'languages'))
            continue;
        $contents = $fs->get($path);
        $lower = mb_strtolower($contents, 'UTF-8');
        foreach ($tokens as $t) {
            if (preg_match('/(^|[^a-zá-ú])' . preg_quote($t, '/') . '([^a-zá-ú]|$)/u', $lower)) {
                $skip = false;
                foreach ($allow as $a) {
                    if (str_contains($lower, $a)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $offenders[] = $path . ' => ' . $t;
                }
            }
        }
    }

    expect($offenders)->toBeEmpty('Portuguese literals found in frontend source: ' . implode(', ', $offenders));
});

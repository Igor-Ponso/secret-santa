<?php

use Tests\Support\Locale;

it('has frontend locale key parity across en/pt_BR/fr', function () {
    $locales = Locale::frontendLocales();
    $enKeys = collect(Locale::loadFrontendKeys('en'));
    foreach (array_diff($locales, ['en']) as $loc) {
        $locKeys = collect(Locale::loadFrontendKeys($loc));
        $missing = $enKeys->diff($locKeys)->values();
        expect($missing)->toBeEmpty("Locale $loc is missing keys: " . $missing->join(', '));
    }
});

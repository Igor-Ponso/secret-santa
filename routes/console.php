<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Custom commands
// groups:run-due-draws is auto-discovered via class, but we can add scheduling example here if desired.
// (Scheduling can also go into app/Console/Kernel.php if created.)

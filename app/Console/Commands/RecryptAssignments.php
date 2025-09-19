<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;

class RecryptAssignments extends Command
{
    protected $signature = 'assignments:recrypt {--from-version=* : Only re-encrypt ciphers currently in these versions} {--force : Re-encrypt even if already in current version} {--chunk=500 : Rows per chunk} {--dry-run : Do not persist changes, just report}';

    protected $description = 'Re-encrypt assignment receiver ciphers using current configured version (optionally filtering by source version)';

    public function handle(): int
    {
        $currentVersion = (string) config('encryption.assignments_version', 1);
        $filterVersions = collect($this->option('from-version'))->filter()->values();
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        $this->line("Target version: v{$currentVersion}");
        if ($filterVersions->isNotEmpty()) {
            $this->line('Filtering source versions: ' . $filterVersions->implode(', '));
        }
        if ($dry) {
            $this->warn('Dry-run mode: no DB writes will occur');
        }

        $total = 0;
        $eligible = 0;
        $updated = 0;
        $skipped = 0;
        $fail = 0;

        Assignment::orderBy('id')->chunk($chunk, function ($rows) use ($currentVersion, $filterVersions, $force, $dry, &$total, &$eligible, &$updated, &$skipped, &$fail) {
            foreach ($rows as $a) {
                $total++;
                if (!$a->receiver_cipher) {
                    $skipped++;
                    continue;
                }
                $version = $this->extractVersion($a->receiver_cipher);
                if ($filterVersions->isNotEmpty() && !$filterVersions->contains($version)) {
                    $skipped++;
                    continue;
                }
                if (!$force && $version === $currentVersion) {
                    $skipped++;
                    continue;
                }

                $eligible++;
                try {
                    $val = $a->decrypted_receiver_id; // decrypt with accessor
                    if ($val === null) {
                        $fail++;
                        continue;
                    }
                    $a->setEncryptedReceiver($val); // will write with current version prefix
                    if (!$dry) {
                        $a->save();
                    }
                    $updated++;
                } catch (\Throwable $e) {
                    $fail++;
                }
            }
        });

        $this->line("Total scanned: {$total}");
        $this->line("Eligible: {$eligible}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failures: {$fail}");

        if ($fail > 0) {
            $this->error('Some records failed to re-encrypt');
        }

        return $fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function extractVersion(string $cipher): string
    {
        if (str_starts_with($cipher, 'v')) {
            $pos = strpos($cipher, ':');
            if ($pos !== false) {
                return substr($cipher, 1, $pos - 1);
            }
        }
        return 'legacy';
    }
}

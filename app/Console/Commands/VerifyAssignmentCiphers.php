<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;

class VerifyAssignmentCiphers extends Command
{
    protected $signature = 'assignments:verify-ciphers {--chunk=500 : Number of rows per chunk}';

    protected $description = 'Verify decryptability of assignment receiver ciphers and report statistics';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $total = 0;
        $withCipher = 0;
        $legacyOnly = 0;
        $ok = 0;
        $fail = 0;
        $versions = [];

        Assignment::orderBy('id')->chunk($chunk, function ($rows) use (&$total, &$withCipher, &$legacyOnly, &$ok, &$fail, &$versions) {
            foreach ($rows as $a) {
                $total++;
                if ($a->receiver_cipher) {
                    $withCipher++;
                    $version = $this->extractVersion($a->receiver_cipher);
                    $versions[$version] = ($versions[$version] ?? 0) + 1;
                    try {
                        $val = $a->decrypted_receiver_id; // accessor triggers decrypt
                        if ($val) {
                            $ok++;
                        } else {
                            $fail++;
                        }
                    } catch (\Throwable $e) {
                        $fail++;
                    }
                } elseif ($a->receiver_user_id) {
                    $legacyOnly++;
                } else {
                    // neither cipher nor legacy value – treat as fail
                    $fail++;
                }
            }
        });

        $this->line('Total assignments: ' . $total);
        $this->line('With cipher: ' . $withCipher);
        $this->line('Legacy only: ' . $legacyOnly);
        $this->line('Decryption OK: ' . $ok);
        $this->line('Decryption Fail: ' . $fail);
        if (!empty($versions)) {
            $this->line('Versions:');
            foreach ($versions as $v => $count) {
                $this->line("  v{$v}: {$count}");
            }
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

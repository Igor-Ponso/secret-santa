<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('public_code', 16)->nullable()->unique()->after('join_code');
        });

        // Backfill existing rows in chunks to avoid locking large tables (table expected small now but safe pattern).
        DB::table('groups')->orderBy('id')->chunk(200, function ($groups) {
            foreach ($groups as $g) {
                if ($g->public_code)
                    continue;
                $code = self::generateUniqueCode();
                DB::table('groups')->where('id', $g->id)->update(['public_code' => $code]);
            }
        });

        // Enforce not null after backfill
        Schema::table('groups', function (Blueprint $table) {
            $table->string('public_code', 16)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique(['public_code']);
            $table->dropColumn('public_code');
        });
    }

    private static function generateUniqueCode(): string
    {
        // 12 char base62 random (length 12 inside 16 column space for possible future versioning)
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($alphabet);
        do {
            $code = '';
            for ($i = 0; $i < 12; $i++) {
                $code .= $alphabet[random_int(0, $len - 1)];
            }
            $exists = DB::table('groups')->where('public_code', $code)->exists();
        } while ($exists);
        return $code;
    }
};

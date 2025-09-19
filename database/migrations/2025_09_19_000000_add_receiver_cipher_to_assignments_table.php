<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->text('receiver_cipher')->nullable()->after('receiver_user_id');
        });

        // Backfill existing rows: encrypt receiver_user_id into receiver_cipher, then null plain id
        DB::table('assignments')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->receiver_user_id && !$row->receiver_cipher) {
                    $cipher = Crypt::encryptString((string) $row->receiver_user_id);
                    DB::table('assignments')->where('id', $row->id)->update([
                        'receiver_cipher' => $cipher,
                        // keep plain for now (allow rollback) - a later migration step could drop it
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('receiver_cipher');
        });
    }
};

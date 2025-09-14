<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('groups', 'min_gift_cents')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->unsignedInteger('min_gift_cents')->nullable()->after('description');
            });
        }
        if (!Schema::hasColumn('groups', 'max_gift_cents')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->unsignedInteger('max_gift_cents')->nullable()->after('min_gift_cents');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('groups', 'min_gift_cents')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('min_gift_cents');
            });
        }
        if (Schema::hasColumn('groups', 'max_gift_cents')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('max_gift_cents');
            });
        }
    }
};

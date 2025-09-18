<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasColumn('groups', 'has_draw')) {
                $table->boolean('has_draw')->default(false)->after('draw_at');
                $table->index('has_draw');
            }
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'has_draw')) {
                $table->dropIndex(['has_draw']);
                $table->dropColumn('has_draw');
            }
        });
    }
};

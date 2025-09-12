<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('group_invitations', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('declined_at');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('group_invitations', function (Blueprint $table) {
            $table->dropIndex(['revoked_at']);
            $table->dropColumn('revoked_at');
        });
    }
};

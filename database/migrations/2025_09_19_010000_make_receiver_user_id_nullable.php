<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('receiver_user_id')->nullable()->change();
        });

        // Null out all legacy plain values now that cipher exists
        DB::table('assignments')->whereNotNull('receiver_user_id')->update(['receiver_user_id' => null]);
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('receiver_user_id')->nullable(false)->change();
        });
    }
};

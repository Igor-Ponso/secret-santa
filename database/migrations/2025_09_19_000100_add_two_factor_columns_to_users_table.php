<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_mode', 32)->default('disabled'); // disabled | email_on_new_device
            $table->timestamp('two_factor_email_enabled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_mode', 'two_factor_email_enabled_at']);
        });
    }
};

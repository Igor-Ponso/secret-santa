<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint_hash', 64); // sha256 hex
            $table->string('device_label', 100)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 max
            $table->string('client_name', 120)->nullable(); // parsed UA friendly name
            $table->string('client_os', 80)->nullable();
            $table->string('client_browser', 80)->nullable();
            $table->string('token_hash', 64); // hashed trust token
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'fingerprint_hash']);
            $table->index(['user_id', 'last_used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_trusted_devices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('description', 280)->nullable();
            $table->unsignedInteger('min_gift_cents')->nullable();
            $table->unsignedInteger('max_gift_cents')->nullable();
            $table->timestamp('draw_at')->nullable();
            $table->boolean('has_draw')->default(false);
            $table->string('join_code', 24)->nullable();
            $table->timestamps();
            $table->index(['owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};

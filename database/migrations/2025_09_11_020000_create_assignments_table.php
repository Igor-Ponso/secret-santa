<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('giver_user_id');
            $table->unsignedBigInteger('receiver_user_id');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('giver_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['group_id', 'giver_user_id']);
            $table->unique(['group_id', 'receiver_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};

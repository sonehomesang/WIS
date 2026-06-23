<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// notifications — in-app bell (Phase 6.10). ຜູ້ຮັບ = user_id, mark read ດ້ວຍ read_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');           // recipient
            $table->enum('type', ['info', 'success', 'warning', 'error'])->default('info');
            $table->string('title', 256);
            $table->text('message')->nullable();
            $table->string('link', 256)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

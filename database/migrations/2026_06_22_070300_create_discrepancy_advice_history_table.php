<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// discrepancy_advice_history — audit trail (append-only). SCHEMA.md §8.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discrepancy_advice_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('discrepancy_advices')->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('status', 32);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 256)->nullable();
            $table->string('role', 64)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['record_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancy_advice_history');
    }
};

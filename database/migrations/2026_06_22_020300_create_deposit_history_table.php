<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// deposit_history — audit trail (append-only) ຕໍ່ deposit record. SCHEMA.md §6.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('deposit_records')->cascadeOnDelete();
            $table->string('action', 32);   // create/submit/accept/confirmStored/flagIssue/confirmFixed/confirmClaim/cancel/...
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
        Schema::dropIfExists('deposit_history');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** disposal_history — audit trail (append-only) ຕໍ່ ໃບ ຈຳໜ່າຍ. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('disposal_records')->cascadeOnDelete();
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
        Schema::dropIfExists('disposal_history');
    }
};

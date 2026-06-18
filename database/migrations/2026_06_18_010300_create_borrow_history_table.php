<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// borrow_history — append-only audit trail ຕໍ່ borrow record (timeline). ເບິ່ງ WORKFLOWS.md §2.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('borrow_records')->cascadeOnDelete();
            $table->string('action', 32);           // create/submit/acknowledge/approve/confirmTake/...
            $table->string('status', 32);           // status ຫຼັງ action
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
        Schema::dropIfExists('borrow_history');
    }
};

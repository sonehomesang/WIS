<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// borrow_return_events — ໜຶ່ງ ແຖວ ຕໍ່ "ຄັ້ງ" ຮັບຄືນ (progressive partial return, ເປີດ ດ້ວຍ workflow.borrow.partial_return).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_return_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('borrow_records')->cascadeOnDelete();
            $table->unsignedInteger('seq');                 // ຄັ້ງ ທີ 1,2,3… ຕໍ່ ໃບ
            $table->date('returned_on');
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->string('received_by_name', 256)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['record_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_return_events');
    }
};

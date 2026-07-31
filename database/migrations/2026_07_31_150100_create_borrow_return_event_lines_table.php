<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// borrow_return_event_lines — ຈຳນວນ ຮັບຄືນ ຕໍ່ ລາຍການ ໃນ ແຕ່ ລະ ຄັ້ງ (event).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_return_event_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('borrow_return_events')->cascadeOnDelete();
            $table->foreignId('borrow_item_id')->constrained('borrow_items')->cascadeOnDelete();
            $table->unsignedInteger('qty');                 // ຮັບຄືນ ຄັ້ງ ນີ້
            $table->text('condition')->nullable();

            $table->index(['event_id']);
            $table->index(['borrow_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_return_event_lines');
    }
};

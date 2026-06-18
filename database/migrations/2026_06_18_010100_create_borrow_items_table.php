<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// borrow_items — ລາຍການເຄື່ອງຕໍ່ borrow record. ເບິ່ງ SCHEMA.md §5.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('borrow_records')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable(); // → inventory_items (null = free-text)
            $table->string('item_name', 256);                  // snapshot
            $table->unsignedInteger('qty');
            $table->unsignedInteger('return_qty')->nullable();  // partial return
            $table->text('condition_on_take')->nullable();
            $table->text('condition_on_return')->nullable();
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_items');
    }
};

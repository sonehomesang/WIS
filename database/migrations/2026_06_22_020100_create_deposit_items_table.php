<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// deposit_items — ລາຍການເຄື່ອງຝາກ ຕໍ່ deposit record. SCHEMA.md §6.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('deposit_records')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable(); // → inventory_items (null = free-text, ປົກກະຕິ)
            $table->string('item_name', 256);
            $table->text('description')->nullable();
            $table->unsignedInteger('qty');
            $table->string('unit', 32)->nullable();
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->enum('currency', ['LAK', 'THB', 'USD'])->nullable();
            $table->text('condition_on_deposit')->nullable();
            $table->text('condition_on_claim')->nullable();
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_items');
    }
};

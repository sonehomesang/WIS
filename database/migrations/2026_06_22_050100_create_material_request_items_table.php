<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// material_request_items — ລາຍການເບີກ ຕໍ່ໃບ (snapshot ຈาก materials catalog). SCHEMA.md §7.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('material_requests')->cascadeOnDelete();
            $table->unsignedBigInteger('material_id')->nullable(); // → materials (null = free-text)
            $table->string('material_nbr', 64)->nullable();         // snapshot
            $table->string('category', 128)->nullable();
            $table->string('description', 500);
            $table->string('unit', 32)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->enum('currency', ['LAK', 'THB', 'USD'])->default('THB');

            // per-item approve / supplier / receiver
            $table->enum('item_status', ['approved', 'rejected'])->nullable();
            $table->string('item_reject_reason', 500)->nullable();
            $table->unsignedInteger('supplier_quantity')->nullable();
            $table->boolean('receiver_confirmed')->default(false);
            $table->string('receiver_note', 500)->nullable();

            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_items');
    }
};

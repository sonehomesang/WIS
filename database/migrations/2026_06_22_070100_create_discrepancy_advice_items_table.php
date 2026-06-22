<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// discrepancy_advice_items — ລາຍການເຄື່ອງ ທີ່ມີຄວາມຜິດ. SCHEMA.md §8.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discrepancy_advice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('discrepancy_advices')->cascadeOnDelete();
            $table->string('stock_code', 128)->nullable();
            $table->string('description', 500);
            $table->unsignedInteger('qty_ordered')->default(0);
            $table->unsignedInteger('qty_delivered')->default(0);
            $table->unsignedInteger('qty_received')->default(0);
            $table->text('comments')->nullable();
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancy_advice_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// oga_items — ລາຍການ packing list. SCHEMA.md §9.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oga_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('outwards_goods_advices')->cascadeOnDelete();
            $table->string('description', 500);
            $table->string('unit', 32)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_weight_kg', 12, 2)->nullable();
            $table->decimal('total_weight_kg', 12, 2)->nullable();
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oga_items');
    }
};

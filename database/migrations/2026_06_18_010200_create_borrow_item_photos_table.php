<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// borrow_item_photos — ຮູບ take/return ຕໍ່ borrow item. ເບິ່ງ SCHEMA.md §5.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_item_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_item_id')->constrained('borrow_items')->cascadeOnDelete();
            $table->enum('kind', ['take', 'return']);
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['borrow_item_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_item_photos');
    }
};

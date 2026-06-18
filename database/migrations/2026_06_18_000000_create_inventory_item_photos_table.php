<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// inventory_item_photos — ຮູບຫຼາຍໃບຕໍ່ item (child table). ເບິ່ງ SCHEMA.md §4.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['item_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_photos');
    }
};

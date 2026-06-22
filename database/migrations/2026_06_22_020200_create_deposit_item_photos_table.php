<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// deposit_item_photos — ຮູບ deposit/stored/claim ຕໍ່ deposit item. SCHEMA.md §6.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_item_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_item_id')->constrained('deposit_items')->cascadeOnDelete();
            $table->enum('kind', ['deposit', 'stored', 'claim']);
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['deposit_item_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_item_photos');
    }
};

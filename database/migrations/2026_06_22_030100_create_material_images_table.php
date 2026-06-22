<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// material_images — ຮູບສິນຄ້າ (≤10/material). SCHEMA.md §3.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['material_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_images');
    }
};

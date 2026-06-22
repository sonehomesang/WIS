<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// material_request_item_photos — ຮູບ ປະກອບ/ຮັບເຄື່ອງ ຕໍ່ລາຍການ. SCHEMA.md §7.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_item_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_item_id')->constrained('material_request_items')->cascadeOnDelete();
            $table->enum('kind', ['request', 'receive'])->default('request');
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['request_item_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_item_photos');
    }
};

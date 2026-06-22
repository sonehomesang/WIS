<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// discrepancy_advice_photos — ຮູບ 3 ປະເພດ (overview/defect/comparison). SCHEMA.md §8.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discrepancy_advice_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('discrepancy_advices')->cascadeOnDelete();
            $table->enum('kind', ['overview', 'defect', 'comparison']);
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancy_advice_photos');
    }
};

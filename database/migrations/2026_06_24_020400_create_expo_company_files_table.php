<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// expo_company_files — ຮູບ/ເອກະສານ ຕໍ່ບໍລິສັດ (product/booth/brochure). SCHEMA.md §10.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expo_company_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('expo_companies')->cascadeOnDelete();
            $table->enum('kind', ['product', 'booth', 'brochure'])->default('product');
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['company_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expo_company_files');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// oga_photos — 6 ປະເພດ: dispatch (loaded/sealed/paper_pli) + delivery (delivered/handover/receipt). SCHEMA.md §9.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oga_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('outwards_goods_advices')->cascadeOnDelete();
            $table->enum('kind', ['loaded', 'sealed', 'paper_pli', 'delivered', 'handover', 'receipt']);
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oga_photos');
    }
};

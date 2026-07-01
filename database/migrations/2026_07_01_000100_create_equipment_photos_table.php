<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// equipment_photos — ຮູບ ຫຼາຍ ໃບ ຕໍ່ ເຄື່ອງ (ສູງສຸດ 3, ບັງຄັບ ໃນ ແອັບ). child table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('path', 500);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['equipment_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_photos');
    }
};

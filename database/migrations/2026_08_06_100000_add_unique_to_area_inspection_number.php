<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// unique index ໃສ່ inspection_number (ຄື ໂມດູລ ອື່ນ) — ກັນ ເລກ ຊ້ຳ ຈາກ concurrency. nullable → ຫຼາຍ NULL ໄດ້.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('area_inspections', function (Blueprint $table) {
            $table->unique('inspection_number');
        });
    }

    public function down(): void
    {
        Schema::table('area_inspections', function (Blueprint $table) {
            $table->dropUnique(['inspection_number']);
        });
    }
};

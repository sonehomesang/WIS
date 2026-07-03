<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຜູກ ການ ກວດກາ ກັບ ແມ່ແບບ + ເກັບ snapshot ຂອງ ຜົນ ເຊັກລິສ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('equipment_id');
            $table->json('checklist')->nullable()->after('result');   // [{label,status,note}]
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'checklist']);
        });
    }
};

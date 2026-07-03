<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            // ປະເພດ ນ້ຳມັນ ຕອນ ກວດ (ev | engine) — ໃຊ້ ກັບ ແມ່ແບບ ທີ່ ລາຍການ ຂຶ້ນ ຕາມ ປະເພດ.
            $table->string('fuel_type', 16)->nullable()->after('template_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
        });
    }
};

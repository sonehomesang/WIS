<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            // ຮອບ ການ ກວດ ຕອນ ບັນທຶກ: pre_use | monthly | quarterly | semi_annual | annual.
            $table->string('frequency', 16)->nullable()->after('fuel_type');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropColumn('frequency');
        });
    }
};

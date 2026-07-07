<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຜູກ ບັນທຶກ ບຳລຸງ ກັບ ແມ່ແບບ + ເກັບ snapshot ຜົນ ເຊັກລິສ (mirror ຂອງ inspection).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_maintenances', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('equipment_id');
            $table->json('checklist')->nullable()->after('status');   // [{label,remark,action,status}]
        });
    }

    public function down(): void
    {
        Schema::table('equipment_maintenances', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'checklist']);
        });
    }
};

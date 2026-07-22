<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// link ໃບ ສ້ອມ (CM) ກັບ ໃບ ບຳລຸງ/ກວດ ຕົ້ນທາງ ທີ່ ພົບ NG (C2). nullable.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_maintenances', function (Blueprint $table) {
            $table->unsignedBigInteger('source_maintenance_id')->nullable()->after('template_id');
            $table->index('source_maintenance_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_maintenances', function (Blueprint $table) {
            $table->dropIndex(['source_maintenance_id']);
            $table->dropColumn('source_maintenance_id');
        });
    }
};

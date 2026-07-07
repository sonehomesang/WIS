<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຜູກ ແມ່ແບບ ບຳລຸງ ກັບ ເຄື່ອງ ໃນ ທະບຽນ — ປະເພດ ດຶງ ຈາກ ເຄື່ອງ ໂດຍ ອັດຕະໂນມັດ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_id')->nullable()->after('name');
            $table->index('equipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_templates', function (Blueprint $table) {
            $table->dropIndex(['equipment_id']);
            $table->dropColumn('equipment_id');
        });
    }
};

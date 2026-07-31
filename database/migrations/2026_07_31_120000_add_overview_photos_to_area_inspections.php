<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຮູບ ພາບ ລວມ ຂອງ ສະຖານທີ່ (ຕໍ່ ໃບ ກວດ) — array ຂອງ path (ຝັງ ວັນ+ເວລາ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('area_inspections', function (Blueprint $table) {
            $table->json('overview_photos')->nullable()->after('checklist');
        });
    }

    public function down(): void
    {
        Schema::table('area_inspections', function (Blueprint $table) {
            $table->dropColumn('overview_photos');
        });
    }
};

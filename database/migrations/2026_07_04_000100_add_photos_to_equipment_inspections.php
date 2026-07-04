<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            // ຮູບ ຫຼັກຖານ ຫຼາຍ ໃບ (path array). photo_path ເກົ່າ ຍັງ ຄົງ ໄວ້ = ຮູບ ທຳອິດ (list).
            $table->json('photos')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};

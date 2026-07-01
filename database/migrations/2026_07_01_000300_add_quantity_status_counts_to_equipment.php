<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ເຄື່ອງ 1 ລາຍການ = ຫຼາຍ ໜ່ວຍ: ເພີ່ມ quantity + unit_id (UoM master) ແລະ ປ່ຽນ
// ສະຖານະ ດ່ຽວ → status_counts (ຈຳນວນ ຕໍ່ ສະຖານະ: active/repair/retired).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('serial_no');
            $table->unsignedBigInteger('unit_id')->nullable()->after('quantity'); // → uoms
            $table->json('status_counts')->nullable()->after('unit_id');           // {active,repair,retired}
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit_id', 'status_counts']);
            $table->enum('status', ['active', 'repair', 'retired'])->default('active');
            $table->index('status');
        });
    }
};

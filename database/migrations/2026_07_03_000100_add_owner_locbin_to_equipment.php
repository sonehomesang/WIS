<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Owner = ພະແນກ ເຈົ້າຂອງ ເຄື່ອງ (ດຶງ ຈາກ departments) — ໃຊ້ ຄຸມ scope ຂອງ department_admin.
            $table->foreignId('department_id')->nullable()->after('category')
                ->constrained('departments')->nullOnDelete();
            // Loc-Bin = ບ່ອນ ວາງ/ຊັ້ນ ໃນ ສາງ (ຂໍ້ຄວາມ ອິດສະຫຼະ ເຊັ່ນ A-01-03).
            $table->string('loc_bin', 64)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('loc_bin');
        });
    }
};

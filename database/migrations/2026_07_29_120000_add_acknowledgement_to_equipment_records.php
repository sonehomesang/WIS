<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manager/Leader ຮັບຊາບ (acknowledge) ໃສ່ ໃບ ກວດ ສະພາບ + ໃບ ບຳລຸງຮັກສາ.
 *
 * ຜູ້ ທີ່ ມີ ສິດ equipment.activate ກົດ ຮັບຊາບ → ບັນທຶກ ຜູ້ + ວັນທີ, ແລ້ວ ຊື່ + ວັນທີ
 * ຂຶ້ນ ໃນ ຫ້ອງ ລາຍເຊັນ "ຜູ້ ຮັບຊາບ" ຂອງ PDF ອັດຕະໂນມັດ.
 */
return new class extends Migration
{
    private array $tables = ['equipment_inspections', 'equipment_maintenances'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('acknowledged_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $t->string('acknowledged_by_name')->nullable()->after('acknowledged_by');
                $t->timestamp('acknowledged_at')->nullable()->after('acknowledged_by_name');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('acknowledged_by');
                $t->dropColumn(['acknowledged_by_name', 'acknowledged_at']);
            });
        }
    }
};

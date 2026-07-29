<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * delete-with-reason + Deleted Log ສຳລັບ Organization (units, departments)
 * + Facilities (locations, buildings, rooms, building_types).
 *
 * ທຸກ ຕາຕະລາງ ນີ້ ໃຊ້ SoftDeletes ຢູ່ ແລ້ວ — ຕື່ມ ພຽງ deleted_reason + deleted_by.
 */
return new class extends Migration
{
    private array $tables = ['units', 'departments', 'locations', 'buildings', 'rooms', 'building_types'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('deleted_reason', 500)->nullable()->after('deleted_at');
                $t->foreignId('deleted_by')->nullable()->after('deleted_reason')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('deleted_by');
                $t->dropColumn('deleted_reason');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            // ຄະແນນ ຜ່ານ (%) ຄິດໄລ່ ຈາກ ເຊັກລິສ — null ຖ້າ ບໍ່ ໃຊ້ ເຊັກລິສ.
            $table->unsignedTinyInteger('score')->nullable()->after('result');
            // ເກັບ ວັນທີ+ເວລາ ຕອນ ບັນທຶກ (ແທນ date ລ້ວນໆ) ເພື່ອ ສະແຕັມ ເວລາ submit.
            $table->dateTime('inspected_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropColumn('score');
            $table->date('inspected_at')->change();
        });
    }
};

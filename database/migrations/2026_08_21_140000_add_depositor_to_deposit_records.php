<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            // ຜູ້ ເອົາ ມາ ຝາກ (depositor) — ອາດ ເປັນ ຄົນ ນອກ ລະບົບ; free-text, optional.
            // ບໍ່ ຄື owner_user_id (= ຜູ້ ຮັບ / ຜູ້ ບັນທຶກ) ຫຼື owner_unit_id (= ໜ່ວຍງານ ເຈົ້າ ຂອງ).
            $table->string('depositor_name', 256)->nullable()->after('owner_email');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->dropColumn('depositor_name');
        });
    }
};

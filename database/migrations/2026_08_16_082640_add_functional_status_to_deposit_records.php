<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ສະຖານະ ການ ໃຊ້ງານ / functional status ຂອງ ເຄື່ອງ ຝາກ (ໃຊ້ໄດ້ · ບາງສ່ວນ · ໃຊ້ບໍ່ໄດ້).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->string('functional_status', 20)->nullable()->after('origin_source');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->dropColumn('functional_status');
        });
    }
};

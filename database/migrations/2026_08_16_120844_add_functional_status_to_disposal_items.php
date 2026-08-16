<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// functional ຂອງ ເຄື່ອງ — ໄຫຼ ມາ ຈາກ Deposit ຕອນ ດຶງ (ຫຼື ພິມ ເອງ), ເກັບ ຢູ່ ໃບ ຈຳໜ່າຍ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_items', function (Blueprint $table) {
            $table->string('functional_status', 20)->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('disposal_items', function (Blueprint $table) {
            $table->dropColumn('functional_status');
        });
    }
};

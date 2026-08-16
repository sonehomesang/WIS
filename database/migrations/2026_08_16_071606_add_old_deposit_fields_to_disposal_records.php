<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ເຄື່ອງຝາກເກົ່າ: ວັນທີຝາກເດີມ + ຜູ້ຮັບຝາກເດີມ (ສຳລັບ ໃບ ຈຳໜ່າຍ ເຄື່ອງ ຝາກ ທີ່ ຄ້າງ ດົນ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_records', function (Blueprint $table) {
            $table->date('original_deposit_date')->nullable()->after('note');
            $table->string('original_receiver', 256)->nullable()->after('original_deposit_date');
        });
    }

    public function down(): void
    {
        Schema::table('disposal_records', function (Blueprint $table) {
            $table->dropColumn(['original_deposit_date', 'original_receiver']);
        });
    }
};

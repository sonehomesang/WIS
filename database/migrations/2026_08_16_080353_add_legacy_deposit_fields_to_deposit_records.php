<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ເຄື່ອງຝາກເກົ່າ: type ທີ 3 (legacy) + ວັນທີຝາກເດີມ + ຜູ້ຮັບຝາກເດີມ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            // ຂະຫຍາຍ enum → string ເພື່ອ ຮັບ 'legacy' (ຄື ຮູບແບບ disposal status)
            $table->string('request_type', 20)->default('walk_in')->change();
            $table->date('original_deposit_date')->nullable()->after('origin_source');
            $table->string('original_receiver', 256)->nullable()->after('original_deposit_date');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->dropColumn(['original_deposit_date', 'original_receiver']);
            $table->enum('request_type', ['walk_in', 'pre_request'])->default('walk_in')->change();
        });
    }
};

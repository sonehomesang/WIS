<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຂະຫຍາຍ status → string ເພື່ອ ຮັບ lifecycle ຈຳໜ່າຍ: 'disposal' (ຖືກ ດຶງ ເຂົ້າ ໃບ ຈຳໜ່າຍ, ລັອກ)
// ແລະ 'disposed' (ຈຳໜ່າຍ ສຳເລັດ — ເຄື່ອງ ບໍ່ ມີ ຕົວຕົນ ແລ້ວ · ລິສ ຕາຍ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'accepted', 'stored', 'needs_fix', 'claimed', 'cancelled'])->default('draft')->change();
        });
    }
};

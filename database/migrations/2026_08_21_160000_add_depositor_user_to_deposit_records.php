<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            // ຄົນ ໃນ (internal depositor) = link ຫາ users; ຄົນ ນອກ = depositor_name (free-text) ເທົ່ານັ້ນ.
            // depositor_name ຍັງ ເກັບ ຊື່ ສະເໝີ (resolve ຈາກ user ຕອນ ບັນທຶກ) ເພື່ອ ພິມ/ສະແດງ ງ່າຍ.
            $table->foreignId('depositor_user_id')->nullable()->after('depositor_name')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deposit_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depositor_user_id');
        });
    }
};

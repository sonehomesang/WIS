<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // ຜູ້ຮັບຜິດຊອບ = ລິ້ງ ຫາ ຜູ້ໃຊ້ (nullOnDelete). responsible_name ຍັງ ຄົງ ໄວ້
            // ເປັນ ຄ່າ ສະແດງ/legacy (ຖ້າ ບໍ່ ໄດ້ ລິ້ງ ຜູ້ໃຊ້).
            $table->foreignId('responsible_user_id')->nullable()->after('responsible_name')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_user_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_items', function (Blueprint $table) {
            // ຄຳ ແນະນຳ / ຄຳ ເຫັນ ຕໍ່ ລາຍການ (ໃຊ້ ໃນ letterhead index; ຂຽນ ຍາວ ໄດ້). Editable.
            $table->text('recommendation')->nullable()->after('condition_status');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_items', function (Blueprint $table) {
            $table->dropColumn('recommendation');
        });
    }
};

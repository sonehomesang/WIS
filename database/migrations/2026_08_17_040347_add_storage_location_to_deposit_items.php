<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ບ່ອນ ຈັດ ເກັບ ໄວ້ ຕໍ່ ລາຍການ (ໃສ່ ຕັ້ງແຕ່ ໜ້າງານ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_items', function (Blueprint $table) {
            $table->string('storage_location', 256)->nullable()->after('condition_on_deposit');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_items', function (Blueprint $table) {
            $table->dropColumn('storage_location');
        });
    }
};

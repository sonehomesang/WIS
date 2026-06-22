<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// shop_prices = ລາຄາจาก supplier ອື່ນໆ ສຳລັບສິນຄ້າດຽວກັນ (price comparison snapshot). Phase 6.7b+.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->json('shop_prices')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->dropColumn('shop_prices');
        });
    }
};

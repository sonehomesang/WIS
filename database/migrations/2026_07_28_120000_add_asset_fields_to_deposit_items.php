<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_items', function (Blueprint $t) {
            $t->string('asset_code', 64)->nullable()->after('item_name');       // ທະບຽນເຄື່ອງ
            $t->string('fixed_asset_no', 64)->nullable()->after('asset_code');  // ທະບຽນຊັບສິນ
        });
    }

    public function down(): void
    {
        Schema::table('deposit_items', function (Blueprint $t) {
            $t->dropColumn(['asset_code', 'fixed_asset_no']);
        });
    }
};

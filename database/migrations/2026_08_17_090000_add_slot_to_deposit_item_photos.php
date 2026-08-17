<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// slot — ມູມ ຮູບ ຝາກ ຕອນ ຮັບ: overall (ຊູມ ລວມ) · id (ລະຫັດ ເຄື່ອງ/ຊັບສິນ) · damage (ຈຸດ ເປ ເພ).
// ຢູ່ ໃນ kind='deposit'; nullable ເພື່ອ ຮອງຮັບ ຮູບ stored/claim ທີ່ ບໍ່ ມີ slot.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_item_photos', function (Blueprint $table) {
            $table->string('slot', 16)->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_item_photos', function (Blueprint $table) {
            $table->dropColumn('slot');
        });
    }
};

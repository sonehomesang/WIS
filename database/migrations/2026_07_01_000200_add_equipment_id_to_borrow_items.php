<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ເຊື່ອມ borrow ⇄ Equipment register: ຕອນ ຢືມ ເລືອກ ທາງເລືອກ 2 (ເຄື່ອງມື/ອຸປະກອນ)
// → ອ້າງອີງ equipment ຈາກ ທະບຽນ. workflow ຢືມ/ສົ່ງຄືນ ບໍ່ ປ່ຽນ (equipment_id ເກັບ ໄວ້ ເສີຍໆ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_items', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_id')->nullable()->after('item_id'); // → equipment (null = inventory/free-text)
        });
    }

    public function down(): void
    {
        Schema::table('borrow_items', function (Blueprint $table) {
            $table->dropColumn('equipment_id');
        });
    }
};

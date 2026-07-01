<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຮັບ kind ໃໝ່ 'request' (ຮູບ ຕໍ່ ລາຍການ ຕອນ ຢືມ) ນອກ ເໜືອ 'take'/'return'.
// ປ່ຽນ enum → string ເພື່ອ ບໍ່ ຕ້ອງ alter enum ອີກ ໃນ ອະນາຄົດ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_item_photos', function (Blueprint $table) {
            $table->string('kind', 16)->change();
        });
    }

    public function down(): void
    {
        Schema::table('borrow_item_photos', function (Blueprint $table) {
            $table->enum('kind', ['take', 'return'])->change();
        });
    }
};

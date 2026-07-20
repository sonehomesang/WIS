<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ບັງຄັບ ຕັ້ງ ລະຫັດ ໃໝ່ ຕອນ login ຄັ້ງ ທຳອິດ (ໃຊ້ ຄູ່ ກັບ ລະຫັດ ຊົ່ວຄາວ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};

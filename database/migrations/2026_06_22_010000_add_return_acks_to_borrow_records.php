<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2-step return: borrower ແຈ້ງສົ່ງ (borrower_return_ack) → warehouse ຢືນຢັນ (wh_return_ack). Phase 6.6.12.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->boolean('borrower_return_ack')->default(false)->after('borrower_return_date');
            $table->boolean('wh_return_ack')->default(false)->after('borrower_return_ack');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropColumn(['borrower_return_ack', 'wh_return_ack']);
        });
    }
};

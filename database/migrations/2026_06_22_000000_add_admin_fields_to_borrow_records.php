<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// return remarks / admin notes / borrower-reported return date (Phase 6.6.11 admin edit).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->text('return_remarks')->nullable()->after('returned_at');
            $table->text('admin_notes')->nullable()->after('return_remarks');
            $table->date('borrower_return_date')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropColumn(['return_remarks', 'admin_notes', 'borrower_return_date']);
        });
    }
};

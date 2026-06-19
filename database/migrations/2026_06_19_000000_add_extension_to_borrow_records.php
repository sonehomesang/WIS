<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// extension request fields ໃນ borrow_records (Phase 6.6.8). 1 pending extension ຕໍ່ record.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->enum('extension_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('cancel_reason');
            $table->text('extension_reason')->nullable()->after('extension_status');
            $table->date('extension_proposed_date')->nullable()->after('extension_reason');
            $table->unsignedBigInteger('extension_requested_by')->nullable()->after('extension_proposed_date');
            $table->timestamp('extension_requested_at')->nullable()->after('extension_requested_by');
            $table->unsignedBigInteger('extension_decided_by')->nullable()->after('extension_requested_at');
            $table->timestamp('extension_decided_at')->nullable()->after('extension_decided_by');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropColumn([
                'extension_status', 'extension_reason', 'extension_proposed_date',
                'extension_requested_by', 'extension_requested_at', 'extension_decided_by', 'extension_decided_at',
            ]);
        });
    }
};

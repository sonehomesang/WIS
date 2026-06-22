<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Soft-delete + audit (deleted log + restore). Phase 6.6.12.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('borrow_records', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['deleted_reason', 'deleted_by']);
        });
    }
};

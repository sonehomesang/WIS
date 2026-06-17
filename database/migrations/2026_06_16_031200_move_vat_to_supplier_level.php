<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// VAT moves to supplier level (default = global) + change history. Contracts keep records only (no VAT).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable();   // null = use global setting
        });

        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn(['vat_enabled', 'vat_rate']);
        });

        Schema::create('supplier_vat_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('old_rate', 5, 2)->nullable();    // null = was global
            $table->decimal('new_rate', 5, 2)->nullable();    // null = set to global
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->index(['supplier_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_vat_changes');
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->boolean('vat_enabled')->default(true);
            $table->decimal('vat_rate', 5, 2)->default(10);
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });
    }
};

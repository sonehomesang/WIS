<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// supplier_contracts — per-supplier contracts with VAT override. (code v1: types/supplier.ts)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('contract_number', 64);
            $table->date('sign_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->boolean('vat_enabled')->default(true);
            $table->decimal('vat_rate', 5, 2)->default(10);   // 0..100, override global /settings/vat
            $table->enum('status', ['draft', 'active', 'expired', 'superseded', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contracts');
    }
};

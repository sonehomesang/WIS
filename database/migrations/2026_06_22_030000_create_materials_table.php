<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// materials — ບັນຊີສິນຄ້າ supplier (Shops Material / catalog). SCHEMA.md §3. Phase 6.7a.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('material_nbr', 64)->nullable();   // SKU / Material No.
            $table->string('category', 128);
            $table->text('description');
            $table->string('unit', 32)->nullable();           // UoM
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->enum('currency', ['LAK', 'THB', 'USD'])->default('THB');
            $table->string('lead_time', 64)->nullable();

            // ສັນຍາ (ອ້າງອີງ ບໍ່ແມ່ນ workflow)
            $table->string('contract_number', 128)->nullable();
            $table->date('contract_date')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('last_price_update')->nullable();

            $table->text('remark')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->index('material_nbr');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};

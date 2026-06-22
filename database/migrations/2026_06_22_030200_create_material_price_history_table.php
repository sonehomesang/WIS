<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// material_price_history — ປະຫວັດການປ່ຽນລາຄາ (ລາຄາ + ສະກຸນ + ເລກສັນຍາ + ວັນທີ + ໃຜ). SCHEMA.md §3.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->enum('currency', ['LAK', 'THB', 'USD'])->nullable();
            $table->string('contract_number', 128)->nullable();
            $table->date('contract_date')->nullable();
            $table->date('update_date');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['material_id', 'update_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_price_history');
    }
};

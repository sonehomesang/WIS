<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// suppliers — external party. ເບິ່ງ SCHEMA.md §3.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 128)->unique();
            $table->string('name', 256);
            $table->string('name_en', 256)->nullable();
            $table->string('contact_person', 256)->nullable();
            $table->string('contact_phone', 64)->nullable();
            $table->string('contact_email', 256)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id', 64)->nullable();
            $table->string('payment_terms', 128)->nullable();
            $table->enum('default_currency', ['LAK', 'THB', 'USD'])->default('LAK');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('is_active');
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'name_en', 'contact_person']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// expo_contacts — ຜູ້ຕິດຕໍ່ ຕໍ່ບໍລິສັດ. SCHEMA.md §10.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expo_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('expo_companies')->cascadeOnDelete();
            $table->string('name', 256);
            $table->enum('role', ['agent', 'representative', 'direct_employee', 'other'])->default('direct_employee');
            $table->string('title', 256)->nullable();
            $table->string('email', 256)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('app_contact', 128)->nullable();  // WhatsApp / WeChat / etc.
            $table->text('notes')->nullable();
            $table->string('business_card_path', 500)->nullable();
            $table->integer('sort_order')->default(0);

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expo_contacts');
    }
};

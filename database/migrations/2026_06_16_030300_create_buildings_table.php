<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// buildings — ອາຄານ, ຂຶ້ນກັບ location. ເບິ່ງ SCHEMA.md §2.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('slug', 128)->unique();
            $table->string('name', 256);
            $table->string('name_en', 256)->nullable();
            $table->string('code', 64)->nullable();
            $table->enum('type', ['office', 'warehouse', 'workshop', 'powerhouse', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['location_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// inventory_items — internal warehouse stock (borrow domain). ເບິ່ງ SCHEMA.md §4.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 128)->unique();
            $table->string('name', 256);
            $table->text('description')->nullable();
            $table->string('category', 128)->nullable();
            $table->string('brand', 128)->nullable();
            $table->string('model', 128)->nullable();
            $table->string('serial_number', 128)->nullable();

            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('min_quantity')->default(0);
            $table->string('unit', 32)->nullable();

            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('shelf_label', 64)->nullable();

            $table->enum('status', ['available', 'borrowed', 'maintenance', 'low-stock'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->string('qr_code', 256)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['is_active', 'status']);
            $table->index('category');
            $table->index('location_id');
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'description', 'brand', 'category']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

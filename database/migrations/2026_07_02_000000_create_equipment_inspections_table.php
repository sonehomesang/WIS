<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ການ ກວດກາ ເຄື່ອງ (Equipment tab 2). ຮູບ ຝັງ ວັນທີ+ເວລາ (stamp) ໄວ້ ໃນ ໄຟລ໌.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->date('inspected_at');
            $table->string('inspector_name', 128)->nullable();
            $table->string('result', 16);                 // pass | fail | follow_up
            $table->text('notes')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('photo_path', 512)->nullable();  // stamped image
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['equipment_id', 'inspected_at']);
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspections');
    }
};

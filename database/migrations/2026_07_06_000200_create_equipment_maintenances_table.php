<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->string('type', 20);                        // preventive | repair | service | other
            $table->string('title', 256);                      // ຫົວຂໍ້ ວຽກ
            $table->text('description')->nullable();            // ລາຍລະອຽດ
            $table->string('performed_by', 256)->nullable();    // ຊ່າງ/ຮ້ານ (optional)
            $table->decimal('cost', 14, 2)->nullable();         // ຄ່າ ໃຊ້ຈ່າຍ ກີບ (optional)
            $table->string('frequency', 16)->nullable();        // ຮອບ service: monthly|quarterly|semi_annual|annual
            $table->date('next_service_date')->nullable();      // service ຄັ້ງ ໜ້າ
            $table->string('status', 16)->default('done');      // planned | in_progress | done
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();                 // ຮູບ ຫຼັກຖານ
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['equipment_id', 'maintenance_date']);
            $table->index('next_service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenances');
    }
};

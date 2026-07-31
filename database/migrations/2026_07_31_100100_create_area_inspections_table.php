<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ບັນທຶກ ການ ກວດ ສະຖານທີ່ (Area/Workplace Inspection). ຜູກ Facilities location (ຫວ່າງ ໄດ້)
// ຫຼື ພິມ ຊື່ ສະຖານທີ່ ເອງ. checklist ເປັນ JSON [{label, requirement, status C|NC|NA, observation, photo}].
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number', 32)->nullable();   // AI{year}-NNNN
            $table->foreignId('template_id')->nullable()->constrained('area_inspection_templates')->nullOnDelete();

            // ສະຖານທີ່ — ຜູກ Facilities (ຫວ່າງ ໄດ້) + label (ຄ່າ ສະແດງ/PDF; free-text ຖ້າ ບໍ່ ຜູກ).
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('location_label', 256)->nullable();

            $table->date('inspected_on');
            $table->string('inspected_time', 16)->nullable();       // "8:00"
            $table->string('frequency', 16)->default('monthly');
            $table->json('inspectors')->nullable();                 // ["Phouvanh", "...", "..."] (ສูงສุด 3)
            $table->json('checklist')->nullable();                  // [{label, requirement, status, observation, photo}]
            $table->string('result', 16)->default('compliant');     // compliant | has_nc
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();

            // ຫົວໜ້າ ຮັບຊາບ (ຄື equipment inspection).
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->string('acknowledged_by_name', 128)->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('deleted_reason', 500)->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['location_id', 'inspected_on']);
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_inspections');
    }
};

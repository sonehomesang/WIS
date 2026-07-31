<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ແມ່ແບບ ເຊັກລິສ ກວດ ສະຖານທີ່ (Area/Workplace Inspection) — ຄື InspectionTemplate ແຕ່ ສຳລັບ ພື້ນທີ່.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 256);
            $table->string('frequency', 16)->default('monthly');   // daily|weekly|monthly|quarterly|annual
            $table->json('items')->nullable();                     // [{label, requirement}]
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('deleted_reason', 500)->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_inspection_templates');
    }
};

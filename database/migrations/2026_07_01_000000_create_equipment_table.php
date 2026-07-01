<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// equipment — ທະບຽນ ເຄື່ອງມື/ເຄື່ອງຈັກ (Equipment & Tools register). Inspection / usage /
// maintenance logs ຈະ ເປັນ ຕາຕະລາງ ແຍກ (ແທັບ 2-4) ທີ່ ອ້າງອີງ equipment_id.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 32)->unique();          // ລະຫັດເຄື່ອງ EQ-0001 (ພາຍໃນ)
            $table->string('fixed_asset_no', 64)->nullable();     // ທະບຽນຊັບສິນ (Fixed Asset) — ເລກ ຈາກ ບັນຊີ ຊັບສິນ
            $table->string('name', 256);
            $table->string('category', 128)->nullable();
            $table->string('brand_model', 256)->nullable();       // ຍີ່ຫໍ້ / ຮຸ່ນ
            $table->string('serial_no', 128)->nullable();
            $table->string('location', 128)->nullable();
            $table->string('responsible_name', 128)->nullable();
            $table->enum('status', ['active', 'repair', 'retired'])->default('active');
            $table->string('photo_path', 512)->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};

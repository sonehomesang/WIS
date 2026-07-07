<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ແມ່ແບບ ເຊັກລິສ ບຳລຸງຮັກສາ (ຕໍ່ ປະເພດ ເຄື່ອງ). admin CRUD ເຕັມ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 256);
            $table->string('category', 128)->nullable();   // ປະເພດ ເຄື່ອງ ທີ່ ໃຊ້ (ວ່າງ = ທົ່ວໄປ)
            $table->text('method')->nullable();             // ວິທີ/ໝາຍເຫດ ບຳລຸງ
            $table->json('items')->nullable();              // [{label, freqs:[monthly,...]}]
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_templates');
    }
};

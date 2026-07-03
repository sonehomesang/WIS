<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ແມ່ແບບ ການ ກວດກາ + ເຊັກລິສ (ຕໍ່ ປະເພດ ເຄື່ອງ). admin CRUD ເຕັມ.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 256);
            $table->string('category', 128)->nullable();   // ປະເພດ ເຄື່ອງ ທີ່ ໃຊ້ (ວ່າງ = ທົ່ວໄປ)
            $table->text('method')->nullable();             // ວິທີ ກວດ (ຄຳ ອະທິບາຍ)
            $table->json('items')->nullable();              // ["ຂໍ້ ເຊັກລິສ 1", ...]
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
        Schema::dropIfExists('inspection_templates');
    }
};

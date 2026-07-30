<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * disposal_signoffs — ຂັ້ນ ເຊັນ ຮັບຮອງ (1 ແຖວ ຕໍ່ ຄົນ ທີ່ ເຊັນ).
 *   role_key: preparer | committee | technical | manager | executive
 *   ຄະນະກຳມະການ (committee) = ຫຼາຍ ແຖວ ໄດ້.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('disposal_records')->cascadeOnDelete();
            $table->string('role_key', 24);
            $table->unsignedInteger('stage_order')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name', 256)->nullable();
            $table->string('title', 256)->nullable();
            $table->string('decision', 16)->default('approved'); // approved | rejected
            $table->text('comment')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['record_id', 'stage_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_signoffs');
    }
};

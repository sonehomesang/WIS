<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * disposal_records — ໃບ ຂໍ ຈຳໜ່າຍ/ທຳລາຍ ເຄື່ອງ ຊຳລຸດ. state machine ຄື transaction ອື່ນ.
 * draft → committee_review → technical_review → manager_review → executive_review
 *       → approved → disposed | rejected(→draft) | cancelled
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_records', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique(); // DS{YYYY}-NNNN
            $table->string('title', 256)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->text('note')->nullable();

            // Preparer (snapshot)
            $table->unsignedBigInteger('prepared_by_user_id')->nullable();
            $table->string('prepared_by_name', 256)->nullable();
            $table->timestamp('prepared_at')->nullable();

            // Reject (loop back to draft) / cancel
            $table->text('reject_reason')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // ອັບເດດ ທະບຽນ ຕົ້ນທາງ ຫຼັງ ອະນຸມັດ ສຳເລັດ (ຖາມ ຢືນຢັນ ກ່ອນ — Phase 6)
            $table->timestamp('registers_updated_at')->nullable();
            $table->unsignedBigInteger('registers_updated_by')->nullable();

            $table->enum('status', [
                'draft', 'committee_review', 'technical_review', 'manager_review',
                'executive_review', 'approved', 'disposed', 'rejected', 'cancelled',
            ])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'created_at']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_records');
    }
};

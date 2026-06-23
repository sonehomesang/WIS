<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// expo_events — ບັນທຶກການໄປ expo + report (Phase 6.9). SCHEMA.md §10.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expo_events', function (Blueprint $table) {
            $table->id();
            $table->string('expo_number', 32)->unique(); // EXP{YYYY}-NNNN
            $table->string('title', 256);
            $table->text('topic')->nullable();           // ປະເພດ / ຫົວข้อ
            $table->text('background')->nullable();       // ປະຫວັດ
            $table->string('venue', 256)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('country', 128)->nullable();
            $table->string('address', 500)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('total_companies_at_expo')->nullable();
            $table->text('feedback')->nullable();         // ພາບລວມ / ຄວາມໜ້າສົນໃຈ
            $table->string('next_event_location', 256)->nullable();
            $table->text('next_proposal')->nullable();    // ຂໍ້ສະເໜີ ຄັ້ງຕໍ່ໄປ
            $table->enum('status', ['draft', 'finalized'])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'start_date']);
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expo_events');
    }
};

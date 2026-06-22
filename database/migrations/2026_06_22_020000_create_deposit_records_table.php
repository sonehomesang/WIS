<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// deposit_records — ການຝາກເຄື່ອງ (state machine 7 states). Pattern ດຽວກັນກັບ borrow. SCHEMA.md §6.
// draft → submitted → accepted → stored ⇄ needs_fix → claimed | cancelled
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_records', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique(); // DP{YYYY}-NNNN

            // Owner (snapshot)
            $table->unsignedBigInteger('owner_user_id');
            $table->string('owner_email', 256);
            $table->string('owner_name', 256);
            $table->unsignedBigInteger('owner_unit_id')->nullable();
            $table->unsignedBigInteger('owner_dept_id')->nullable();

            // Request meta
            $table->enum('request_type', ['walk_in', 'pre_request'])->default('walk_in');
            $table->string('item_category', 256)->nullable();
            $table->string('origin_source', 500)->nullable();
            $table->text('deposit_reason')->nullable();
            $table->string('expected_duration', 128)->nullable();
            $table->date('deposit_date');
            $table->date('expected_arrival')->nullable();   // pre_request
            $table->date('expected_claim_date')->nullable();
            $table->date('actual_claim_date')->nullable();
            $table->text('remark')->nullable();

            // Warehouse accept (assign storage)
            $table->unsignedBigInteger('warehouse_staff_user_id')->nullable();
            $table->string('warehouse_staff_name', 256)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('warehouse_instructions')->nullable();
            $table->string('storage_location', 256)->nullable();
            $table->string('storage_shelf_label', 128)->nullable();

            // Stored
            $table->timestamp('stored_at')->nullable();
            $table->unsignedBigInteger('stored_by')->nullable();

            // Needs-fix loop
            $table->text('needs_fix_reason')->nullable();
            $table->timestamp('needs_fix_flagged_at')->nullable();
            $table->unsignedBigInteger('needs_fix_flagged_by')->nullable();

            // Claim
            $table->timestamp('claimed_at')->nullable();
            $table->unsignedBigInteger('claimed_by')->nullable();

            // Cancel
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->enum('status', ['draft', 'submitted', 'accepted', 'stored', 'needs_fix', 'claimed', 'cancelled'])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'deposit_date']);
            $table->index(['owner_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_records');
    }
};

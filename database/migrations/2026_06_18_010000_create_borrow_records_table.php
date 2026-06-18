<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// borrow_records — ການຍືມເຄື່ອງ (state machine). ເບິ່ງ WORKFLOWS.md §2 + SCHEMA.md §5.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_records', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique(); // BR{YYYY}-NNNN

            // Borrower (snapshot)
            $table->unsignedBigInteger('borrower_user_id');
            $table->string('borrower_email', 256);
            $table->string('borrower_name', 256);
            $table->unsignedBigInteger('borrower_unit_id')->nullable();
            $table->unsignedBigInteger('borrower_dept_id')->nullable();

            // Type + purpose + period
            $table->enum('borrow_type', ['new_inventory', 'tools_equipment', 'deposited_tools', 'others'])->default('new_inventory');
            $table->unsignedBigInteger('deposit_record_id')->nullable(); // → deposit (Phase 6.8)
            $table->string('others_detail', 500)->nullable();
            $table->text('purpose')->nullable();
            $table->text('remark')->nullable();
            $table->date('borrow_date');
            $table->unsignedSmallInteger('period_days')->default(7);
            $table->date('planned_return_date');
            $table->date('actual_return_date')->nullable();

            // Workflow — acknowledge (line manager, optional per config)
            $table->boolean('requires_acknowledge')->default(false);
            $table->unsignedBigInteger('acknowledge_user_id')->nullable();
            $table->string('acknowledge_email', 256)->nullable();
            $table->string('acknowledge_name', 256)->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            // Workflow — approve
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->string('approver_email', 256)->nullable();
            $table->string('approver_name', 256)->nullable();
            $table->timestamp('approved_at')->nullable();

            // Workflow — warehouse take/return
            $table->unsignedBigInteger('warehouse_staff_user_id')->nullable();
            $table->string('warehouse_staff_name', 256)->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->enum('status', ['draft', 'acknowledged', 'approved', 'active', 'overdue', 'returned', 'cancelled'])->default('draft');
            $table->text('cancel_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'planned_return_date']);
            $table->index(['borrower_user_id', 'status']);
            $table->index(['approver_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_records');
    }
};

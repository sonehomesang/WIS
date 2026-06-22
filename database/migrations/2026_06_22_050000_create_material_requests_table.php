<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// material_requests — ໃບເບີກວັດສະດຸ (procurement, 8-state). SCHEMA.md §7. Phase 6.7b.
// draft → submitted → approved → validated → dispatched → received → completed | rejected | cancelled
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique(); // MR{YYYY}-NNNN

            // Requester (snapshot)
            $table->unsignedBigInteger('requester_user_id');
            $table->string('requester_email', 256);
            $table->string('requester_name', 256);
            $table->unsignedBigInteger('requester_unit_id')->nullable();
            $table->unsignedBigInteger('requester_dept_id')->nullable();

            // Request meta
            $table->text('purpose')->nullable();
            $table->string('request_type', 64)->nullable();   // CM / eForm / project
            $table->string('wo_e_form', 256)->nullable();
            $table->string('wo_type', 32)->nullable();         // single / multiple
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('rooms', 256)->nullable();
            $table->string('functions', 256)->nullable();
            $table->text('remark')->nullable();

            // Supplier
            $table->unsignedBigInteger('assigned_supplier_id')->nullable();

            // Totals + VAT snapshot (frozen at submit)
            $table->enum('currency', ['LAK', 'THB', 'USD'])->default('THB');
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // Workflow — approve
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->string('approver_email', 256)->nullable();
            $table->string('approver_name', 256)->nullable();
            $table->timestamp('approved_at')->nullable();

            // Workflow — warehouse validate
            $table->unsignedBigInteger('warehouse_staff_user_id')->nullable();
            $table->string('warehouse_staff_name', 256)->nullable();
            $table->timestamp('validated_at')->nullable();

            // Workflow — dispatch (supplier / warehouse-on-behalf)
            $table->string('delivery_method', 64)->nullable();   // supplier_delivery / pickup_at_supplier
            $table->date('planned_delivery_date')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();

            // Workflow — receive
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->string('received_by_name', 256)->nullable();
            $table->boolean('invoice_received')->default(false);
            $table->boolean('delivery_note_received')->default(false);
            $table->boolean('spec_match')->default(false);

            // Workflow — close
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->string('invoice_number', 128)->nullable();
            $table->string('sap_reference', 128)->nullable();

            $table->enum('status', ['draft', 'submitted', 'approved', 'validated', 'dispatched', 'received', 'completed', 'rejected', 'cancelled'])->default('draft');
            $table->text('reject_reason')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'created_at']);
            $table->index(['requester_user_id', 'status']);
            $table->index(['approver_user_id', 'status']);
            $table->index(['assigned_supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};

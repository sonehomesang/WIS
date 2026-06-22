<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// discrepancy_advices — ໃບແຈ້ງເຄື່ອງ supplier ສົ່ງຜິດ/ເສຍ (DA). SCHEMA.md §8. Phase 6.8b.
// draft → submitted → purchasing_review → pending_approval → resolved | cancelled
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discrepancy_advices', function (Blueprint $table) {
            $table->id();
            $table->string('da_number', 32)->unique(); // DA{YYYY}-NNNN
            $table->date('date');
            $table->string('po_number', 128)->nullable();
            $table->date('po_date')->nullable();

            // Section A — warehouse intake
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('supplier_name', 256)->nullable();
            $table->string('purchasing_officer_name', 256)->nullable();
            $table->string('vendor_packing_list', 128)->nullable();
            $table->string('vendor_invoice_number', 128)->nullable();
            $table->json('discrepancy_types')->nullable();   // incorrect_supplied/oversupplied/undersupplied/damaged/no_paperwork/other
            $table->text('comments')->nullable();
            $table->string('warehouse_recommendation_kind', 32)->nullable(); // none/accept_goods/return_supplier/direct_charge/other
            $table->text('warehouse_recommendation_text')->nullable();
            $table->unsignedBigInteger('raised_by')->nullable();
            $table->string('raised_by_name', 256)->nullable();
            $table->timestamp('raised_at')->nullable();

            // Section B — purchasing decision
            $table->json('purchasing_decisions')->nullable();
            $table->text('purchasing_note')->nullable();
            $table->string('return_transport_account', 16)->nullable(); // vendor/ntpc
            $table->string('return_transport_mode', 16)->nullable();    // road/air
            $table->string('return_carrier_name', 256)->nullable();
            $table->string('return_carrier_phone', 64)->nullable();
            $table->unsignedBigInteger('purchasing_officer_user_id')->nullable();
            $table->timestamp('purchasing_decided_at')->nullable();

            // Section C — leader approval
            $table->text('resolution_action')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->string('approved_by_name', 256)->nullable();
            $table->string('approved_title', 128)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('next_step', 16)->nullable(); // oga/finished

            $table->enum('status', ['draft', 'submitted', 'purchasing_review', 'pending_approval', 'resolved', 'cancelled'])->default('draft');
            $table->text('reject_reason')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'date']);
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancy_advices');
    }
};

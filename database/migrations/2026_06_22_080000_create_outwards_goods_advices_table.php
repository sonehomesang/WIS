<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// outwards_goods_advices — ໃບສົ່ງເຄື່ອງອອກ (OGA, warehouse-only). SCHEMA.md §9. Phase 6.8c.
// draft → dispatched → delivered | returned ; cancel ຈาก draft
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outwards_goods_advices', function (Blueprint $table) {
            $table->id();
            $table->string('oga_number', 32)->unique(); // OGA{YYYY}-NNNN
            $table->date('date');
            $table->string('po_number', 128)->nullable();
            $table->string('ship_via', 16)->nullable(); // road/air

            // source (optional link to DA)
            $table->string('source_type', 16)->default('oga'); // da/oga/other
            $table->unsignedBigInteger('source_da_id')->nullable();
            $table->string('source_da_number', 32)->nullable();

            // destination (supplier snapshot)
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('dispatch_to_name', 256)->nullable();
            $table->string('dispatch_to_address', 500)->nullable();
            $table->string('dispatch_to_phone', 64)->nullable();
            $table->string('dispatch_to_contact_person', 256)->nullable();
            $table->string('dispatch_to_email', 256)->nullable();

            // cargo
            $table->string('goods_consigned', 500)->nullable();
            $table->string('dimension', 128)->nullable();
            $table->decimal('gross_weight_kg', 12, 2)->nullable();
            $table->decimal('total_weight_kg', 12, 2)->nullable();
            $table->text('reason_of_despatch')->nullable();
            $table->text('comments')->nullable();

            // transport + signatures
            $table->string('driver_name', 256)->nullable();
            $table->string('truck_plate_number', 64)->nullable();
            $table->unsignedBigInteger('consign_by_user_id')->nullable();
            $table->string('consign_by_name', 256)->nullable();
            $table->unsignedBigInteger('authorized_by_user_id')->nullable();
            $table->string('authorized_by_name', 256)->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->unsignedBigInteger('completed_by_user_id')->nullable();
            $table->string('completed_by_name', 256)->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->enum('status', ['draft', 'dispatched', 'delivered', 'returned', 'cancelled'])->default('draft');
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
            $table->index('source_da_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outwards_goods_advices');
    }
};

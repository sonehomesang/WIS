<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ANSI — Application for New Stock Item. Staff apply to add a new material to WH
// Inventories through a 5-stage chain: Originator -> HoS/TL -> Manager -> Warehouse
// (check duplicate, create item number, create PR) -> Closeout. Mirrors the
// Disposal endorsement + Request/Deposit item-row patterns.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ansi_applications', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique();      // ANSI-{YYYY}-NNNN

            // originator (auto from the logged-in user's profile)
            $table->unsignedBigInteger('originator_user_id')->nullable();
            $table->string('originator_name', 256)->nullable();
            $table->string('originator_email', 256)->nullable();
            $table->unsignedBigInteger('owner_unit_id')->nullable();   // Branch/Unit
            $table->unsignedBigInteger('owner_dept_id')->nullable();   // Department
            $table->string('section_team', 128)->nullable();
            $table->string('phone', 64)->nullable();

            // approvers (person pickers)
            $table->unsignedBigInteger('hos_user_id')->nullable();
            $table->string('hos_name', 256)->nullable();
            $table->unsignedBigInteger('manager_user_id')->nullable();
            $table->string('manager_name', 256)->nullable();

            // general info
            $table->date('app_date')->nullable();
            $table->string('sub_assembly', 128)->nullable();
            $table->string('functional_system', 128)->nullable();
            $table->text('purpose')->nullable();
            $table->text('summary_items')->nullable();             // auto from item rows

            $table->enum('status', [
                'draft', 'pending_hos', 'pending_manager', 'pending_warehouse',
                'completed', 'rejected', 'cancelled',
            ])->default('draft');

            // stage stamps
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('endorsed_by')->nullable();      // HoS
            $table->timestamp('endorsed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();      // Manager
            $table->timestamp('approved_at')->nullable();

            // warehouse stage
            $table->unsignedBigInteger('warehoused_by')->nullable();
            $table->timestamp('warehoused_at')->nullable();
            $table->string('pr_number', 64)->nullable();
            $table->text('warehouse_note')->nullable();

            // reject / cancel
            $table->string('reject_stage', 32)->nullable();             // hos | manager | warehouse
            $table->text('reject_reason')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'created_at']);
            $table->index(['owner_dept_id', 'status']);
        });

        Schema::create('ansi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('ansi_applications')->cascadeOnDelete();
            $table->boolean('stock')->default(false);              // Stock: Yes/No
            $table->text('description');                           // Noun, Detail, Model, Brand
            $table->decimal('price_usd', 14, 2)->nullable();
            $table->unsignedInteger('qty_order')->default(1);
            $table->string('unit', 32)->nullable();
            $table->unsignedInteger('min_qty')->nullable();
            $table->unsignedInteger('max_qty')->nullable();
            $table->string('suggested_supplier', 256)->nullable();
            $table->boolean('hazardous')->default(false);          // Yes/No
            $table->boolean('criticality')->default(false);        // Yes/No
            $table->string('special_storage', 32)->default('Normal'); // Normal | Air Cond room
            $table->string('item_number', 64)->nullable();         // set by warehouse
            $table->unsignedBigInteger('created_inventory_id')->nullable(); // v2 hook
            $table->integer('sort_order')->default(0);
            $table->index(['application_id', 'sort_order']);
        });

        Schema::create('ansi_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('ansi_applications')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('original_name', 256)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('ansi_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('ansi_applications')->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('status', 32)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 256)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansi_histories');
        Schema::dropIfExists('ansi_attachments');
        Schema::dropIfExists('ansi_items');
        Schema::dropIfExists('ansi_applications');
    }
};

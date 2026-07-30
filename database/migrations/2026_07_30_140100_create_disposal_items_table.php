<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * disposal_items — ລາຍການ ທີ່ ຂໍ ຈຳໜ່າຍ. ດຶງ ຈາກ Inventory/Equipment/Deposit ຫຼື ພິມ ໃໝ່.
 *   reason/recommendation = ຄ່າ dropdown; *_detail = ຂໍ້ຄວາມ ພິມ ເພີ່ມ.
 *   history = snapshot ຂໍ້ ບັນຫາ+ສ້ອມ/ປ່ຽນ ຈາກ ບຳລຸງ/ກວດ (ສະເພາະ ລາຍການ ຈາກ Equipment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('disposal_records')->cascadeOnDelete();
            $table->string('source_type', 16)->default('new');   // inventory|equipment|deposit|new
            $table->unsignedBigInteger('source_id')->nullable();  // id ໃນ ທະບຽນ ຕົ້ນທາງ
            $table->string('item_name', 256);
            $table->string('asset_code', 64)->nullable();
            $table->string('fixed_asset_no', 64)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->string('unit', 32)->nullable();
            $table->text('condition')->nullable();
            $table->string('reason', 128)->nullable();
            $table->text('reason_detail')->nullable();
            $table->string('recommendation', 128)->nullable();
            $table->text('recommendation_detail')->nullable();
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->enum('currency', ['LAK', 'THB', 'USD'])->nullable();
            $table->json('history')->nullable();   // [{date,kind,problem,action}]
            $table->json('photos')->nullable();    // [storage path]
            $table->integer('sort_order')->default(0);

            $table->index(['record_id', 'sort_order']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_items');
    }
};

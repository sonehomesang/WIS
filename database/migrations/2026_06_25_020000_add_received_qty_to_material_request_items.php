<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            // Quantity actually received (per item) — supports partial receipt.
            $table->unsignedInteger('received_qty')->default(0)->after('supplier_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->dropColumn('received_qty');
        });
    }
};

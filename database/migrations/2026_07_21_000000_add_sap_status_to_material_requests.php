<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SAP PR/FR procurement status — ບັນທຶກຕອນ close (ຄຽງຄູ່ invoice_number + sap_reference).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->string('sap_status', 32)->nullable()->after('sap_reference');
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropColumn('sap_status');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['deleted_reason', 'deleted_by']);
        });
    }
};

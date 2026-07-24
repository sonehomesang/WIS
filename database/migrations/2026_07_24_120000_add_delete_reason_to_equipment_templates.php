<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['inspection_templates', 'maintenance_templates'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->text('deleted_reason')->nullable();
                $t->unsignedBigInteger('deleted_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['inspection_templates', 'maintenance_templates'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['deleted_reason', 'deleted_by']);
            });
        }
    }
};

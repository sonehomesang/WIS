<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('condition_status', 32)->default('in_service')->after('status')->index();
            $table->text('condition_note')->nullable()->after('condition_status');
            $table->timestamp('condition_set_at')->nullable()->after('condition_note');
            $table->unsignedBigInteger('condition_set_by')->nullable()->after('condition_set_at');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex(['condition_status']);
            $table->dropColumn(['condition_status', 'condition_note', 'condition_set_at', 'condition_set_by']);
        });
    }
};

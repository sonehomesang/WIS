<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wis_id = WIS Firestore doc id — ໃຊ້ map ຕอน migrate ຂໍ້ມູນ (idempotent) + map ຮູບ Storage ພายหลัง.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('wis_id', 64)->nullable()->unique()->after('id');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('wis_id', 64)->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('wis_id');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('wis_id');
        });
    }
};

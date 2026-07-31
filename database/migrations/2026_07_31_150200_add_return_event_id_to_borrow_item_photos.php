<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ຜູກ ຮູບ return ກັບ "ຄັ້ງ" ຮັບຄືນ (null = ຮັບຄືນ ຄັ້ງ ດຽວ ແບບ legacy).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_item_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('return_event_id')->nullable()->after('kind');
            $table->index('return_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_item_photos', function (Blueprint $table) {
            $table->dropIndex(['return_event_id']);
            $table->dropColumn('return_event_id');
        });
    }
};

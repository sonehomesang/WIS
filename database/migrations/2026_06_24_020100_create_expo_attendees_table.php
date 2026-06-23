<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// expo_attendees — ພະນັກງານທີ່ມອບໝາຍ + ຄວາມຄິດເຫັນແຕ່ລະຄົน. SCHEMA.md §10.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expo_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('expo_events')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name', 256);   // snapshot
            $table->text('opinion')->nullable(); // ຄວາມຄິດເຫັນ ຕໍ່ງານ + ຄັ້ງໜ້າ

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expo_attendees');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// expo_companies — ບໍລິສັດໜ້າສົນໃຈ ໃນແຕ່ລະງານ. SCHEMA.md §10.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expo_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('expo_events')->cascadeOnDelete();
            $table->string('name', 256);
            $table->string('country', 128)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('website', 256)->nullable();
            $table->string('email', 256)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('other_contacts', 256)->nullable();
            $table->text('products')->nullable();        // ສິນຄ້າ / ບໍລິການ
            $table->text('benefit')->nullable();          // ປະໂຫຍດ / ເໝາະກັບເຮົາ
            $table->enum('interest_level', ['hot', 'warm', 'cold'])->default('warm');
            $table->unsignedTinyInteger('score')->nullable(); // 1-5
            $table->integer('sort_order')->default(0);

            $table->index(['event_id', 'interest_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expo_companies');
    }
};

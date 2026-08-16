<?php

use App\Support\ConditionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ສະຖານະພາບ ເຄື່ອງ (condition status) — ຖານ ຂໍ້ມູນ ດຽວ, admin ຈັດການ ໄດ້.
// key = ຄ່າ ທີ່ ເກັບ ໃນ items.condition_status (ຄົງ ທີ່ ເພື່ອ map ຂໍ້ມູນ ເກົ່າ).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('label', 128);              // "ລາວ · English"
            $table->string('color', 20)->default('gray');
            $table->boolean('is_disposable')->default(false);   // → ດຶງ ໄປ Disposal ໄດ້
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        // seed the canonical defaults so the admin catalogue is populated on deploy
        $now = now();
        foreach (ConditionStatus::DEFAULTS as $i => $d) {
            DB::table('condition_statuses')->insert([
                'key' => $d['key'], 'label' => $d['label'], 'color' => $d['color'],
                'is_disposable' => $d['is_disposable'], 'is_active' => true, 'sort_order' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_statuses');
    }
};

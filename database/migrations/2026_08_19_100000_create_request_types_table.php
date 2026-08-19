<?php

use App\Support\RequestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ປະເພດ ການ ຂໍ ເຄື່ອງ (Material Request type) — ຖານ ຂໍ້ມູນ ດຽວ, admin ຈັດການ ໄດ້.
// key = ຄ່າ ທີ່ ເກັບ ໃນ material_requests.request_type (ຄົງ ທີ່ ເພື່ອ map ຂໍ້ມູນ ເກົ່າ: CM/eForm/project).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('label', 128);              // "CM · Corrective Maintenance"
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        // seed the canonical defaults so the admin catalogue is populated on deploy
        $now = now();
        foreach (RequestType::DEFAULTS as $i => $d) {
            DB::table('request_types')->insert([
                'key' => $d['key'], 'label' => $d['label'],
                'is_active' => true, 'sort_order' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('request_types');
    }
};

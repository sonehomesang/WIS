<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Convert buildings.type (enum) -> building_type_id (FK to building_types).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
        });

        // Backfill from the old enum string (slug matches seeded building_types.slug)
        foreach (DB::table('buildings')->get() as $b) {
            if (! empty($b->type)) {
                $id = DB::table('building_types')->where('slug', $b->type)->value('id');
                if ($id) {
                    DB::table('buildings')->where('id', $b->id)->update(['building_type_id' => $id]);
                }
            }
        }

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_type_id');
        });
        Schema::table('buildings', function (Blueprint $table) {
            $table->enum('type', ['office', 'warehouse', 'workshop', 'powerhouse', 'other'])->default('other');
        });
    }
};

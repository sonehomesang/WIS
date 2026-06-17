<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// building_types — admin-managed dictionary (ແທນ enum hardcode). ເບິ່ງ SCHEMA.md §2.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 128);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        $now = now();
        foreach ([['office', 'Office'], ['warehouse', 'Warehouse'], ['workshop', 'Workshop'], ['powerhouse', 'Powerhouse'], ['other', 'Other']] as [$slug, $name]) {
            DB::table('building_types')->insert([
                'slug' => $slug, 'name' => $name, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('building_types');
    }
};

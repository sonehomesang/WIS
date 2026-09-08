<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer Satisfaction Survey (Warehouse & Import-Export) — one flat row per
 * response so the dashboard can aggregate fast. Ratings are 1–5, all nullable so
 * a respondent can rate only the services they actually use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->nullable()->index();   // respondent's unit
            $table->unsignedBigInteger('user_id')->nullable()->index();   // set when logged in
            $table->string('frequency', 20)->nullable()->index();         // daily|weekly|monthly|occasionally

            // Warehouse service (1–5)
            $table->unsignedTinyInteger('wh_receiving')->nullable();
            $table->unsignedTinyInteger('wh_condition')->nullable();
            $table->unsignedTinyInteger('wh_storage')->nullable();
            // Import & Export service (1–5)
            $table->unsignedTinyInteger('ie_customs')->nullable();
            $table->unsignedTinyInteger('ie_communication')->nullable();
            $table->unsignedTinyInteger('ie_urgent')->nullable();
            // Overall (1–5)
            $table->unsignedTinyInteger('overall_wh')->nullable();
            $table->unsignedTinyInteger('overall_ie')->nullable();

            $table->text('doing_well')->nullable();
            $table->text('improve')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};

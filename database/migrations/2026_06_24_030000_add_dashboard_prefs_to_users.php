<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-user dashboard widget toggles: {kpi, queue, activity, charts}.
            // null = use defaults (all on). See User::dashboardPrefs().
            $table->json('dashboard_prefs')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_prefs');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assigned-endorser model: each disposal_signoffs row can be pre-assigned to a
 * named user who then fills it independently. Adds the endorser's own
 * recommendation, who assigned them, and when they were emailed the link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_signoffs', function (Blueprint $table) {
            $table->string('recommendation', 128)->nullable()->after('comment');
            $table->unsignedBigInteger('assigned_by')->nullable()->after('recommendation');
            $table->timestamp('notified_at')->nullable()->after('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::table('disposal_signoffs', function (Blueprint $table) {
            $table->dropColumn(['recommendation', 'assigned_by', 'notified_at']);
        });
    }
};

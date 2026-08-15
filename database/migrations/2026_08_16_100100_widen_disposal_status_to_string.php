<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * status was an enum (CHECK constraint) that pre-dates the parallel-endorsement
 * model, so the new 'in_review' value was rejected. Widen to a plain string —
 * valid statuses are enforced in DisposalService, not the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_records', function (Blueprint $table) {
            $table->string('status', 32)->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('disposal_records', function (Blueprint $table) {
            $table->enum('status', [
                'draft', 'committee_review', 'technical_review', 'manager_review',
                'executive_review', 'approved', 'disposed', 'rejected', 'cancelled',
            ])->default('draft')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail for user-account actions (create / activate / lock /
 * unlock / update). Mirrors the per-module *_history tables so it plugs into the
 * existing Settings › Audit log union: record_id = the TARGET user, user_name =
 * the ACTOR (which admin), created_at = when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('users')->cascadeOnDelete();  // target user
            $table->string('action', 32);    // create / activate / lock / unlock / update
            $table->string('status', 32);     // resulting status
            $table->unsignedBigInteger('user_id')->nullable();   // actor id
            $table->string('user_name', 256)->nullable();        // actor name
            $table->string('role', 64)->nullable();              // actor role
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['record_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_history');
    }
};

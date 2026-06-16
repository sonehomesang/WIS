<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                          // external ref (replaces Firebase UID)

            // Identity / auth
            $table->string('email', 256)->unique();
            $table->string('username', 64)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Profile
            $table->string('display_name', 256);                     // was Breeze 'name'
            $table->string('phone_number', 32)->nullable();
            $table->string('photo_path', 500)->nullable();           // storage path (not URL)

            // Org binding (index only — no FK, per SCHEMA §1)
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();   // FK added when suppliers table exists

            // Status + RBAC
            $table->enum('status', ['pending', 'active', 'locked'])->default('pending');
            $table->enum('auth_provider', ['password', 'google', 'domain'])->default('password');
            $table->boolean('is_pre_created')->default(false);
            $table->boolean('is_super_admin')->default(false);       // "above admin" privilege flag

            // Security audit
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('unit_id');
            $table->index('supplier_id');

            // FULLTEXT is MySQL-only — skip on sqlite (test DB uses :memory:)
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['display_name', 'email']);
            }
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

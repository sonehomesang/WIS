<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unambiguous marker for accounts CREATED by the AD import.
 *
 * The kill switch previously scoped on auth_provider=domain + is_pre_created,
 * but this app already pre-creates real staff with exactly those flags — so a
 * rollback also deleted genuine staff accounts. Only rows stamped here were
 * created by the importer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ldap_imported_at')->nullable()->index()->after('ad_guid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ldap_imported_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the role-based access control + account-status fields used by
     * CheckRole / EnsureUserIsActive (same pattern as events_portal and
     * digital_cards_portal), plus a lightweight login-activity trail and
     * soft deletes so a removed account can be recovered/audited instead
     * of the row disappearing outright.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('password');
            $table->string('status')->default('active')->after('role');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_ip');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'last_login_at', 'last_login_ip', 'last_activity_at']);
            $table->dropSoftDeletes();
        });
    }
};

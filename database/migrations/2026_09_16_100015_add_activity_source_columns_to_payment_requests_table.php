<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive-only: two nullable FKs linking a Payment Request back to
     * the Activity Budget/Retirement it was generated for (Activity
     * Advance / Activity Reimbursement respectively - the "purpose" is
     * derived from which one is set, not stored as a third column, to
     * avoid a redundant value that could drift out of sync). Mutually
     * exclusive - enforced in the service layer in a later phase, not at
     * the DB level. Both null (every existing Payment Request today, and
     * every future one not generated from this module) behaves exactly
     * as before this migration; no existing Payment Request behavior
     * changes.
     *
     * nullOnDelete on both: if an activity_budgets/activity_retirements
     * row were ever removed, the Payment Request - the actual financial
     * instrument - must survive regardless.
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->foreignId('source_activity_budget_id')->nullable()->after('id')
                ->constrained('activity_budgets')->nullOnDelete();
            $table->foreignId('source_activity_retirement_id')->nullable()->after('source_activity_budget_id')
                ->constrained('activity_retirements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_activity_budget_id');
            $table->dropConstrainedForeignId('source_activity_retirement_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per budget VERSION for an Activity. unique(activity_id,
     * version) is the hard integrity rule from the approved architecture.
     * `is_current` marks the version currently in effect - creating an
     * amendment (a new Draft version) must NOT flip the previous
     * Approved version's is_current to false; only that new version's own
     * Approval does (a later phase's ActivityBudgetWorkflowService
     * responsibility, done inside a DB transaction with lockForUpdate()
     * on both rows - nothing here enforces that ordering at the DB level
     * beyond the plain uniqueness constraint, by design, since "only one
     * is_current=true row per activity" is a workflow-time invariant, not
     * a column-level one).
     *
     * total_cash/total_invoice/total_overall are derived from
     * activity_budget_items and re-persisted here (for fast listing/
     * dashboard queries) every time items change - never independently
     * editable. Advance-received figures deliberately do NOT live here;
     * see activity_advance_disbursements (multiple tranches, approved
     * architecture decision).
     */
    public function up(): void
    {
        Schema::create('activity_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();

            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_current')->default(true);

            $table->string('budget_code')->nullable();
            $table->string('status')->default('draft');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('currency', 3);
            $table->decimal('total_cash', 18, 2)->default(0);
            $table->decimal('total_invoice', 18, 2)->default(0);
            $table->decimal('total_overall', 18, 2)->default(0);
            $table->decimal('requested_advance_amount', 18, 2)->default(0);

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comment')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->text('returned_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['activity_id', 'version']);
            $table->index('status');
            $table->index('current_assignee_id');
            $table->index(['activity_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_budgets');
    }
};

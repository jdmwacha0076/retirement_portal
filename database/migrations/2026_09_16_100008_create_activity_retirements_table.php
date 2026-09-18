<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `reference` (e.g. RET/2026/00001) is its own permanent system
     * identifier - a retirement is never identified only by its
     * activity's reference, per the approved architecture.
     *
     * Deliberately NO unique(activity_budget_id) constraint: MySQL has no
     * partial/filtered unique index, so "one NON-CANCELLED retirement per
     * budget version" cannot be expressed at the column level without
     * also blocking a legitimate new retirement after a previous one was
     * cancelled. That rule is enforced at the application layer in a
     * later phase's ActivityRetirementWorkflowService::start(), inside
     * DB::transaction() + lockForUpdate(), exactly as approved.
     *
     * total_advanced/unspent_advance_amount/reimbursement_due_amount are
     * derived (SUM of activity_advance_disbursements and the retirement
     * items) and cached here at key workflow points (computed and frozen
     * once the retirement reaches Approved/ReconciliationPending) -
     * never a source of truth independent of the underlying records.
     */
    public function up(): void
    {
        Schema::create('activity_retirements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            $table->foreignId('activity_budget_id')->constrained('activity_budgets')->restrictOnDelete();

            $table->string('status')->default('draft');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->text('returned_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->decimal('total_actual_cash', 18, 2)->default(0);
            $table->decimal('total_actual_invoice', 18, 2)->default(0);
            $table->decimal('total_actual_overall', 18, 2)->default(0);

            $table->decimal('total_advanced', 18, 2)->default(0);
            $table->decimal('unspent_advance_amount', 18, 2)->nullable();
            $table->decimal('reimbursement_due_amount', 18, 2)->nullable();

            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('activity_budget_id');
            $table->index('status');
            $table->index('current_assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_retirements');
    }
};

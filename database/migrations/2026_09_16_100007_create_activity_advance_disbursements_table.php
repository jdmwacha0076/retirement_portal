<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only ledger of actual advance disbursements against an
     * approved ActivityBudget - supports multiple tranches by design
     * (approved architecture decision). total_advanced is always
     * SUM(amount) for a budget, never a single field. payment_request_id
     * is nullable (manual disbursements are allowed) but UNIQUE when
     * present, so a later phase's PaymentRequestWorkflowService::
     * markPaid() hook can safely create exactly one disbursement row per
     * paid Payment Request even under a retry/double-click - MySQL
     * permits multiple NULLs in a unique index, so manual disbursements
     * (payment_request_id = null) are unaffected.
     */
    public function up(): void
    {
        Schema::create('activity_advance_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_budget_id')->constrained('activity_budgets')->restrictOnDelete();
            $table->foreignId('payment_request_id')->nullable()->unique()->constrained('payment_requests')->nullOnDelete();

            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('disbursed_at');
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')->constrained('users');

            $table->timestamps();

            $table->index('activity_budget_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_advance_disbursements');
    }
};

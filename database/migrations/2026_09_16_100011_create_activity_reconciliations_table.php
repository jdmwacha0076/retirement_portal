<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only ledger of unspent-advance-return / reimbursement
     * transactions against a retirement - supports multiple partial
     * transactions by design (approved architecture decision).
     * Outstanding balance is always target_amount - SUM(matching-type
     * rows), computed in a later phase's service, never stored as a
     * single "resolved" flag.
     *
     * payment_request_id is nullable (manual reconciliation is allowed)
     * but UNIQUE when present, for the same idempotency reason as
     * activity_advance_disbursements: a later phase's
     * PaymentRequestWorkflowService::markPaid() hook can safely create
     * exactly one reconciliation row per paid reimbursement Payment
     * Request even under a retry.
     */
    public function up(): void
    {
        Schema::create('activity_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_retirement_id')->constrained('activity_retirements')->restrictOnDelete();

            $table->string('type');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);

            $table->foreignId('payment_request_id')->nullable()->unique()->constrained('payment_requests')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->date('transaction_date');
            $table->string('supporting_document_path')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')->constrained('users');

            $table->timestamps();

            $table->index('activity_retirement_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reconciliations');
    }
};

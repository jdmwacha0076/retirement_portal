<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Core payment/voucher request. Fields shared by most of the 5
     * payment types live here; the 6 fields specific to the two
     * consultancy types live in payment_request_consultancy_details
     * (one-to-one) instead of being nullable columns on every row.
     *
     * amount = the actual amount being disbursed (for consultancy types
     * this equals amount_due, i.e. fee minus withholding tax - the total
     * consultancy fee and tax breakdown live only in the detail table).
     *
     * payee_name is always populated regardless of type (for consultancy
     * requests it's a copy of consultancy_details.consultant_name) so
     * listings/search/filters never need a conditional join just to show
     * "who is this being paid to".
     */
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();

            // Null until first submission; permanent once set (see
            // PaymentRequestWorkflowService::submit()).
            $table->string('reference_number')->nullable()->unique();

            $table->string('payment_type');
            $table->string('status')->default('draft');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('payee_name');
            $table->date('payment_date');
            $table->text('description');

            $table->string('currency', 3)->default('TZS');
            $table->decimal('amount', 18, 2);
            $table->text('amount_in_words');

            // Type-specific single fields that don't justify their own table.
            $table->string('cheque_number')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('cashier_name')->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comment')->nullable();

            // Payment processing
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();

            // Rejection
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Most recent return-for-correction reason, for quick display
            // on the detail page - the full trail is always in
            // payment_request_history regardless.
            $table->text('returned_reason')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_type');
            $table->index('created_by');
            $table->index('current_assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};

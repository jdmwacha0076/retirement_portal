<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * activity_budget_item_id is MANDATORY (never nullable) - this is the
     * approved/actual traceability requirement: every retirement line
     * must answer "what was approved?" as well as "what was actually
     * spent?". unique(activity_retirement_id, activity_budget_item_id)
     * prevents the same approved line from being duplicated twice inside
     * one retirement.
     *
     * The approved payment_mode/cash_amount/invoice_amount/total are
     * read live via the activity_budget_item_id relationship rather than
     * snapshotted onto this row - the parent budget VERSION is already
     * immutable once Approved, so there is no drift risk and no
     * duplicated data.
     *
     * actual_cash_amount + actual_invoice_amount = actual_total is
     * validated in a later phase's service/Form Request, not at the DB
     * level. payment_mode_change_justification is required (validated
     * later) only when actual_payment_mode differs from the approved
     * item's payment_mode; variance_justification only when actual_total
     * exceeds the approved item's total (over-budget) - under-budget
     * never requires justification, per the approved architecture.
     */
    public function up(): void
    {
        Schema::create('activity_retirement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_retirement_id')->constrained('activity_retirements')->restrictOnDelete();
            $table->foreignId('activity_budget_item_id')->constrained('activity_budget_items')->restrictOnDelete();

            $table->decimal('actual_qty', 10, 2)->nullable();
            $table->decimal('actual_unit_cost', 18, 2)->nullable();
            $table->unsignedSmallInteger('actual_frequency')->nullable();

            $table->decimal('actual_cash_amount', 18, 2)->default(0);
            $table->decimal('actual_invoice_amount', 18, 2)->default(0);
            $table->decimal('actual_total', 18, 2)->default(0);
            $table->string('actual_payment_mode')->nullable();

            $table->text('payment_mode_change_justification')->nullable();
            $table->text('variance_justification')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['activity_retirement_id', 'activity_budget_item_id'], 'retirement_item_unique_budget_item');
            $table->index('activity_budget_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_retirement_items');
    }
};

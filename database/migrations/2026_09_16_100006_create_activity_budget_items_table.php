<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Budget line items. total = qty * unit_cost * frequency, always
     * recalculated server-side before save (never trusted from the
     * client) - see the ActivityBudgetCalculator support class added in
     * a later phase. qty is DECIMAL (0.5, 1.5 etc are legitimate
     * quantities); frequency stays an integer count of
     * occurrences/days/trips per the approved architecture decision.
     *
     * cash_amount/invoice_amount implement the Cash/Invoice/Split
     * architecture: Cash -> cash_amount=total, invoice_amount=0; Invoice
     * -> the reverse; Split -> both entered and validated to sum to
     * total (validation lives in a later phase's Form Request/service,
     * not here). Zero is used rather than NULL for the unused side,
     * since zero is semantically correct (there is genuinely no invoice
     * component on a pure-cash item).
     */
    public function up(): void
    {
        Schema::create('activity_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_budget_id')->constrained('activity_budgets')->restrictOnDelete();
            $table->foreignId('budget_category_id')->constrained('budget_categories')->restrictOnDelete();
            $table->foreignId('budget_component_id')->nullable()->constrained('budget_components')->nullOnDelete();

            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('qty', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 18, 2);
            $table->unsignedSmallInteger('frequency')->default(1);
            $table->decimal('total', 18, 2)->default(0);

            $table->string('payment_mode')->default('cash');
            $table->decimal('cash_amount', 18, 2)->default(0);
            $table->decimal('invoice_amount', 18, 2)->default(0);

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('activity_budget_id');
            $table->index('budget_category_id');
            $table->index('budget_component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_budget_items');
    }
};

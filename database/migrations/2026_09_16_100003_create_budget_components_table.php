<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed master data belonging to a category (e.g. "Airport
     * Transfer" under Transport). requires_supporting_document exists
     * from Phase 1 because the retirement-submission guard (a later
     * phase) blocks submission when a component that requires a document
     * has none attached - the flag has to exist before that validation
     * can be written. budget_category_id uses restrictOnDelete (a
     * category can't be hard-deleted while components reference it - not
     * that hard delete is ever exposed anyway; components are
     * deactivated, never removed, for the same historical-integrity
     * reason as the category itself).
     */
    public function up(): void
    {
        Schema::create('budget_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_category_id')->constrained('budget_categories')->restrictOnDelete();

            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_unit')->nullable();
            $table->string('default_payment_mode')->nullable();
            $table->boolean('requires_supporting_document')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->unique(['budget_category_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_components');
    }
};

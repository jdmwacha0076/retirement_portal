<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed master data (Transport, Meals and Credit, Visa,
     * Accommodation, ...). The A/B/C/D letters shown on a printed budget
     * are NOT permanently tied to a category here - they are assigned
     * per-budget based on the order categories appear inside that
     * specific budget. Never hard-deleted once used - deactivated
     * instead, so historical budget lines never lose their category
     * label.
     */
    public function up(): void
    {
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
    }
};

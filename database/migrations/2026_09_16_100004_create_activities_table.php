<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The umbrella project/travel/training record. `reference` (e.g.
     * ACT/2026/00001) is the permanent system identifier, generated once
     * at registration via reference_counters and never changed.
     * `accounting_code` (e.g. LGH27/26) is a SEPARATE, optional,
     * manually-entered donor/project code used only for the printed
     * hierarchical budget item numbering (LGH27/26-A, LGH27/26-A1, ...) -
     * see ActivityBudgetItem.item_code. If accounting_code is null, the
     * budget print falls back to displaying `reference` instead.
     *
     * `status` is deliberately coarse (Draft/Active/Completed/Closed/
     * Cancelled) - the detailed lifecycle stage shown on the activity
     * page is always computed from the current ActivityBudget/
     * ActivityRetirement status, never stored as a separate field, to
     * avoid state-duplication/sync bugs.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->string('accounting_code')->nullable();

            $table->foreignId('activity_type_id')->constrained('activity_types')->restrictOnDelete();

            $table->string('title');
            $table->text('purpose');
            $table->string('program')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->string('venue')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('participant_count')->nullable();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();

            $table->string('currency', 3);

            $table->string('status')->default('draft');

            $table->foreignId('created_by')->constrained('users');

            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('activity_type_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic, append-only assignment/forwarding trail shared by
     * ActivityBudget and ActivityRetirement. Structured columns (not a
     * free-text log) mirror payment_request_assignments exactly. No
     * update/destroy route is ever exposed.
     */
    public function up(): void
    {
        Schema::create('activity_assignments', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignmentable');

            $table->foreignId('assigned_from')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->text('comment')->nullable();
            $table->string('status_at_assignment')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Explicit short name - see the identical fix in
            // 2026_09_16_100012_create_activity_history_table.php for why
            // (MySQL's 64-character identifier limit).
            $table->index(['assignmentable_type', 'assignmentable_id', 'created_at'], 'activity_assignments_morph_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_assignments');
    }
};

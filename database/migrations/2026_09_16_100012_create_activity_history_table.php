<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic, append-only audit trail shared by ActivityBudget and
     * ActivityRetirement (approved architecture decision - avoids
     * duplicating this table once per domain). No update/destroy route
     * is ever exposed for this table, mirroring payment_request_history.
     * $table->morphs('historyable') already adds a
     * (historyable_type, historyable_id) index; the explicit composite
     * below additionally covers created_at for efficient ordered
     * "history for this record" lookups.
     */
    public function up(): void
    {
        Schema::create('activity_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('historyable');

            $table->string('action');
            $table->foreignId('performed_by')->constrained('users');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Explicit short name - MySQL's default auto-generated name for
            // this 3-column index (activity_history_historyable_type_
            // historyable_id_created_at_index) is 68 characters, over
            // MySQL's 64-character identifier limit.
            $table->index(['historyable_type', 'historyable_id', 'created_at'], 'activity_history_morph_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_history');
    }
};

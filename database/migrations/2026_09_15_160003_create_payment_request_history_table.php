<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit log - one row per workflow action of any kind
     * (created, submitted, assigned, reviewed, returned, approved,
     * rejected, marked ready, paid, resubmitted, cancelled). Drives the
     * timeline on the detail page. No update/destroy route is ever
     * exposed for this table from the application - that's what makes
     * "audit records can't be edited/deleted" true, not a UI convention.
     */
    public function up(): void
    {
        Schema::create('payment_request_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->cascadeOnDelete();

            $table->string('action');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_history');
    }
};

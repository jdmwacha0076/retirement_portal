<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structured forward/assign records - a queryable subset of
     * payment_request_history's events, kept as its own table so "who is
     * this currently with" / "requests assigned to me" / "my tasks"
     * queries are a plain WHERE instead of parsing a generic log. Never
     * updated or deleted once written.
     */
    public function up(): void
    {
        Schema::create('payment_request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->cascadeOnDelete();

            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('assigned_from')->nullable()->constrained('users')->nullOnDelete();

            $table->text('comment')->nullable();
            $table->string('status_at_assignment')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_request_id');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_assignments');
    }
};

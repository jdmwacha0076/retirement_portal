<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs atomic reference-number generation (PRX/PAY/{year}/{seq}).
     * One row per year; PaymentRequestWorkflowService::submit() locks the
     * matching row with lockForUpdate() inside a transaction, reads
     * next_number, assigns it, increments, commits - safe under
     * concurrent submissions without relying on MAX(id)+1.
     */
    public function up(): void
    {
        Schema::create('payment_request_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_counters');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per consultancy-type payment request (Consultancy - Cheque /
     * Consultancy - Online only). withholding_tax_percentage is stored
     * per-row rather than always read from config, so changing the
     * default rate later never rewrites the rate on an already-submitted
     * request.
     */
    public function up(): void
    {
        Schema::create('payment_request_consultancy_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->unique()->constrained('payment_requests')->cascadeOnDelete();

            $table->string('consultant_name');
            $table->string('tin_number');
            $table->string('client_name');

            $table->decimal('consultancy_fee', 18, 2);
            $table->decimal('withholding_tax_percentage', 5, 2);
            $table->decimal('withholding_tax_amount', 18, 2);
            $table->decimal('amount_due', 18, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_consultancy_details');
    }
};

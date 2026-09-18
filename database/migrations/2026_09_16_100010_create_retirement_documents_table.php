<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Receipts/supporting documents. activity_retirement_item_id is
     * nullable: null means a general, retirement-level document (e.g.
     * attendance sheet, activity report) not tied to any specific
     * expenditure line. One item can have MANY receipts, and this is
     * never modelled as a single receipt column - always a proper child
     * table.
     */
    public function up(): void
    {
        Schema::create('retirement_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_retirement_id')->constrained('activity_retirements')->restrictOnDelete();
            $table->foreignId('activity_retirement_item_id')->nullable()->constrained('activity_retirement_items')->nullOnDelete();

            $table->string('document_type');
            $table->string('receipt_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->string('vendor')->nullable();
            $table->decimal('document_amount', 18, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->text('description')->nullable();

            $table->string('original_filename');
            $table->string('stored_path');

            $table->foreignId('uploaded_by')->constrained('users');

            $table->timestamps();

            $table->index('activity_retirement_id');
            $table->index('activity_retirement_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_documents');
    }
};

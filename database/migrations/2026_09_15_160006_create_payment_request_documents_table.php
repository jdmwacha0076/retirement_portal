<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supporting documents (invoice, receipt, quotation, contract, PO,
     * approval doc, other). stored_path points into the private
     * 'local' disk (config('payment_requests.documents.disk')) - never
     * the public disk - and is only ever reached through the authorized
     * download route, never a direct URL.
     */
    public function up(): void
    {
        Schema::create('payment_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->cascadeOnDelete();

            $table->string('document_type');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');

            $table->foreignId('uploaded_by')->constrained('users');

            $table->timestamps();

            $table->index('payment_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_documents');
    }
};

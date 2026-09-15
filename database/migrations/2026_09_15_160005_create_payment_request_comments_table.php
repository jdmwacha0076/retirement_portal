<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Internal discussion on a request ("Please attach the correct
     * invoice.") - deliberately separate from payment_request_history,
     * which records workflow *actions*, not conversation.
     */
    public function up(): void
    {
        Schema::create('payment_request_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('body');
            $table->timestamps();

            $table->index('payment_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_comments');
    }
};

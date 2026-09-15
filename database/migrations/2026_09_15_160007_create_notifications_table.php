<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standard Laravel database-notifications table (this fresh Jetstream
     * install didn't ship one). Backs the in-app notification bell added
     * in Stage 10 - assigned-to-you, returned-to-you, approved/rejected,
     * action-required, payment-completed. Nothing writes here until
     * Stage 10's Notification classes exist, but the table needs to
     * exist ahead of that so this migration can run with the rest of
     * this batch.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

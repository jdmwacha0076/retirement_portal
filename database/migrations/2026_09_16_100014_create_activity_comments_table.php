<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic internal comments shared by ActivityBudget and
     * ActivityRetirement. Deliberately a separate table from
     * activity_history - comments are user-authored discussion, history
     * is system-authored fact, and the two must never be confused
     * (approved architecture decision).
     */
    public function up(): void
    {
        Schema::create('activity_comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');

            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');

            $table->timestamps();

            // Explicit short name - see the identical fix in
            // 2026_09_16_100012_create_activity_history_table.php for why
            // (MySQL's 64-character identifier limit).
            $table->index(['commentable_type', 'commentable_id', 'created_at'], 'activity_comments_morph_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_comments');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic internal comments shared by ActivityBudget and
 * ActivityRetirement. Deliberately separate from ActivityHistory -
 * comments are user-authored discussion, history is system-authored
 * fact.
 */
class ActivityComment extends Model
{
    protected $guarded = ['id'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

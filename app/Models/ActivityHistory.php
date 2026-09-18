<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic, append-only audit trail shared by ActivityBudget and
 * ActivityRetirement (approved architecture decision). No update/destroy
 * route is ever exposed for this model - mirrors PaymentRequestHistory.
 */
class ActivityHistory extends Model
{
    const UPDATED_AT = null;

    /**
     * "history" doesn't pluralize to a natural table name, so the
     * migration deliberately created it as `activity_history` (singular)
     * rather than Eloquent's default `activity_histories` guess - this
     * override is required or every insert/query 404s against a
     * nonexistent table.
     */
    protected $table = 'activity_history';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function historyable(): MorphTo
    {
        return $this->morphTo();
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

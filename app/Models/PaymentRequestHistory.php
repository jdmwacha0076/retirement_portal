<?php

namespace App\Models;

use App\Enums\PaymentRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestHistory extends Model
{
    /**
     * Eloquent's default table-name guess pluralizes "history" to
     * "histories" (Str::plural()'s irregular-noun handling), but the
     * migration deliberately created payment_request_history (singular -
     * "history" already reads as a mass/collective noun, so pluralizing
     * it would be wrong English) - pinned explicitly so the two never
     * drift apart again.
     */
    protected $table = 'payment_request_history';

    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_status' => PaymentRequestStatus::class,
            'to_status' => PaymentRequestStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

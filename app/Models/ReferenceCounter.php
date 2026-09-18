<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per (counter_key, year) - e.g. ('activity', 2026). A later
 * phase's ReferenceGenerator support class reads/increments this under
 * DB::transaction() + lockForUpdate(), exactly like PaymentRequestCounter
 * already does for payment_requests. No business logic lives on this
 * model itself (Phase 1 scope - see the approved implementation rules).
 */
class ReferenceCounter extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_sequence' => 'integer',
        ];
    }
}

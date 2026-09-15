<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per calendar year. Only ever touched inside
 * PaymentRequestWorkflowService::generateReferenceNumber(), which locks
 * the row (lockForUpdate) inside a DB transaction before reading/
 * incrementing next_number - never read or written anywhere else.
 */
class PaymentRequestCounter extends Model
{
    protected $guarded = ['id'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single receipt/supporting document. activity_retirement_item_id null
 * means a general, retirement-level document rather than one tied to a
 * specific expenditure line.
 */
class RetirementDocument extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'document_amount' => 'decimal:2',
        ];
    }

    public function activityRetirement(): BelongsTo
    {
        return $this->belongsTo(ActivityRetirement::class);
    }

    public function activityRetirementItem(): BelongsTo
    {
        return $this->belongsTo(ActivityRetirementItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return config("activity_budgets.documents.types.{$this->document_type}", ucfirst(str_replace('_', ' ', $this->document_type)));
    }
}

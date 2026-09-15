<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestDocument extends Model
{
    protected $guarded = ['id'];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return config("payment_requests.documents.types.{$this->document_type}", ucfirst(str_replace('_', ' ', $this->document_type)));
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;

        return $bytes >= 1_048_576
            ? number_format($bytes / 1_048_576, 1).' MB'
            : number_format(max($bytes, 1) / 1024, 1).' KB';
    }
}

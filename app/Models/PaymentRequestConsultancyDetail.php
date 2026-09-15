<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestConsultancyDetail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'consultancy_fee' => 'decimal:2',
            'withholding_tax_percentage' => 'decimal:2',
            'withholding_tax_amount' => 'decimal:2',
            'amount_due' => 'decimal:2',
        ];
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /**
     * Withholding Tax Amount = Total Consultancy Fee x Withholding Tax %
     * Amount Due = Total Consultancy Fee - Withholding Tax Amount
     * Kept as a static helper (not hardcoded inline wherever it's needed)
     * so the create/edit form's server-side recalculation and any future
     * report both call the exact same math.
     *
     * @return array{withholding_tax_amount: float, amount_due: float}
     */
    public static function calculate(float $consultancyFee, float $withholdingTaxPercentage): array
    {
        $taxAmount = round($consultancyFee * ($withholdingTaxPercentage / 100), 2);

        return [
            'withholding_tax_amount' => $taxAmount,
            'amount_due' => round($consultancyFee - $taxAmount, 2),
        ];
    }
}

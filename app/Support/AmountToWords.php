<?php

namespace App\Support;

/**
 * Converts a decimal amount into words for the printed voucher and the
 * "Amount in Words" field, e.g. 1500000.00 + 'TZS' ->
 * "One Million Five Hundred Thousand Tanzanian Shillings Only", or
 * 1500000.50 + 'USD' ->
 * "One Million Five Hundred Thousand United States Dollars and Fifty Cents".
 *
 * Deliberately dependency-free (no intl/NumberFormatter assumption) so it
 * works the same on any PHP install this project is deployed to.
 */
class AmountToWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    private const CURRENCY_NAMES = [
        'TZS' => 'Tanzanian Shillings',
        'USD' => 'United States Dollars',
        'KES' => 'Kenyan Shillings',
        'UGX' => 'Ugandan Shillings',
        'EUR' => 'Euros',
        'GBP' => 'British Pounds',
    ];

    public static function convert(float|string $amount, string $currency): string
    {
        $amount = round((float) $amount, 2);
        $wholePart = (int) floor($amount);
        $cents = (int) round(($amount - $wholePart) * 100);

        $currencyName = self::CURRENCY_NAMES[strtoupper($currency)] ?? strtoupper($currency);

        $words = $wholePart === 0 ? 'Zero' : self::convertWhole($wholePart);
        $sentence = trim($words).' '.$currencyName;

        if ($cents > 0) {
            $sentence .= ' and '.self::convertWhole($cents).' Cents';
        }

        return $sentence.' Only';
    }

    private static function convertWhole(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        // Indian/East-African grouping (Crore/Lakh) is not used here -
        // Praxis's paper forms use standard international grouping
        // (Thousand/Million/Billion), so that's what this follows.
        $segments = [
            [1_000_000_000, 'Billion'],
            [1_000_000, 'Million'],
            [1_000, 'Thousand'],
        ];

        $words = '';

        foreach ($segments as [$value, $label]) {
            if ($number >= $value) {
                $count = intdiv($number, $value);
                $words .= self::convertUnderThousand($count).' '.$label.' ';
                $number %= $value;
            }
        }

        if ($number > 0) {
            $words .= self::convertUnderThousand($number);
        }

        return trim(preg_replace('/\s+/', ' ', $words));
    }

    private static function convertUnderThousand(int $number): string
    {
        $words = '';

        if ($number >= 100) {
            $words .= self::ONES[intdiv($number, 100)].' Hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $words .= self::TENS[intdiv($number, 10)].' ';
            $number %= 10;
        }

        if ($number > 0) {
            $words .= self::ONES[$number].' ';
        }

        return trim($words);
    }
}

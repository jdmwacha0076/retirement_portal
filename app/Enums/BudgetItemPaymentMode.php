<?php

namespace App\Enums;

/**
 * How a single budget/retirement line is funded. Split keeps the schema
 * ready for a cash+invoice mix on one line (cash_amount + invoice_amount
 * both populated) even though the first-release builder UI may still
 * nudge most users toward a plain Cash-or-Invoice choice.
 */
enum BudgetItemPaymentMode: string
{
    case Cash = 'cash';
    case Invoice = 'invoice';
    case Split = 'split';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Invoice => 'Invoice',
            self::Split => 'Split (Cash + Invoice)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Cash => 'status-success',
            self::Invoice => 'status-info',
            self::Split => 'status-secondary',
        };
    }

    /**
     * @return array<string,string> value => label, for select inputs.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $m) => [$m->value => $m->label()])->all();
    }
}

<?php

namespace App\Enums;

/**
 * What an activity_reconciliations transaction represents. Exactly one of
 * these ever applies to a given retirement, since unspent-advance and
 * reimbursement-due are mutually exclusive figures (see
 * ActivityRetirement::unspent_advance_amount / reimbursement_due_amount).
 */
enum ReconciliationType: string
{
    case UnspentReturn = 'unspent_return';
    case Reimbursement = 'reimbursement';

    public function label(): string
    {
        return match ($this) {
            self::UnspentReturn => 'Unspent Advance Returned',
            self::Reimbursement => 'Reimbursement Paid',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::UnspentReturn => 'status-info',
            self::Reimbursement => 'status-warning',
        };
    }

    /**
     * @return array<string,string> value => label, for select inputs.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}

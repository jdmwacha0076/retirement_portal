<?php

namespace App\Enums;

/**
 * The five payment/voucher types Praxis currently uses. Each case carries
 * everything a controller/view needs to branch on (label, icon, which
 * optional core columns it uses, whether it needs the consultancy detail
 * row, which print partial reproduces its paper form) so adding a sixth
 * type later is: add a case here, add its Form Request rules, add its
 * print partial - no schema change unless the new type needs genuinely
 * new fields.
 */
enum PaymentType: string
{
    case Cash = 'cash';
    case OnlineTransfer = 'online_transfer';
    case Cheque = 'cheque';
    case ConsultancyCheque = 'consultancy_cheque';
    case ConsultancyOnline = 'consultancy_online';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash Payment',
            self::OnlineTransfer => 'Online Cash Transfer',
            self::Cheque => 'Cheque Payment',
            self::ConsultancyCheque => 'Consultancy / Outsourced Works - Cheque Payment',
            self::ConsultancyOnline => 'Consultancy - Online Payment',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash Payment',
            self::OnlineTransfer => 'Online Transfer',
            self::Cheque => 'Cheque Payment',
            self::ConsultancyCheque => 'Consultancy - Cheque',
            self::ConsultancyOnline => 'Consultancy - Online',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cash => 'Pay a payee directly in cash, through a cashier.',
            self::OnlineTransfer => 'Pay a payee by direct online/bank transfer.',
            self::Cheque => 'Pay a payee by issuing a cheque.',
            self::ConsultancyCheque => 'Pay a consultant or outsourced contractor by cheque, with withholding tax calculated automatically.',
            self::ConsultancyOnline => 'Pay a consultant or outsourced contractor by online transfer, with withholding tax calculated automatically.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'bi-cash-coin',
            self::OnlineTransfer => 'bi-bank2',
            self::Cheque => 'bi-vector-pen',
            self::ConsultancyCheque => 'bi-briefcase-fill',
            self::ConsultancyOnline => 'bi-briefcase-fill',
        };
    }

    public function requiresConsultancyDetails(): bool
    {
        return in_array($this, [self::ConsultancyCheque, self::ConsultancyOnline], true);
    }

    public function usesChequeNumber(): bool
    {
        return in_array($this, [self::Cheque, self::ConsultancyCheque], true);
    }

    public function usesTransactionReference(): bool
    {
        return in_array($this, [self::OnlineTransfer, self::ConsultancyOnline], true);
    }

    public function usesCashier(): bool
    {
        return $this === self::Cash;
    }

    /**
     * View name for this type's printable voucher. Cash/Online/Cheque
     * share one paper shape; the two consultancy types share another -
     * see resources/views/payment-requests/print/*.
     */
    public function printView(): string
    {
        return $this->requiresConsultancyDetails()
            ? 'payment-requests.print.consultancy'
            : 'payment-requests.print.standard';
    }

    /**
     * Route segment used by the create-form step (e.g. /payment-requests/create/cash).
     */
    public function routeSegment(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public static function fromRouteSegment(string $segment): self
    {
        return self::from(str_replace('-', '_', $segment));
    }

    /**
     * @return array<string,string> value => label, for the type-picker cards and filters.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->shortLabel()])->all();
    }
}

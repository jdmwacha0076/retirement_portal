<?php

namespace App\Enums;

/**
 * Container status for an Activity. Deliberately coarse - the detailed
 * "where are we right now" display (Budget Under Review, Awaiting
 * Retirement, Retirement Under Review, etc.) is computed live from the
 * activity's current ActivityBudget/ActivityRetirement status, never
 * stored here. See Activity::currentStageLabel() (added in a later phase
 * once the workflow services exist) - this enum intentionally stays this
 * short by design (approved architecture decision), not an oversight.
 */
enum ActivityStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'status-draft',
            self::Active => 'status-info',
            self::Completed => 'status-warning',
            self::Closed => 'status-completed',
            self::Cancelled => 'status-archived',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'bi-file-earmark-text',
            self::Active => 'bi-play-circle-fill',
            self::Completed => 'bi-flag-fill',
            self::Closed => 'bi-check-circle-fill',
            self::Cancelled => 'bi-slash-circle-fill',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Cancelled],
            self::Active => [self::Completed, self::Cancelled],
            self::Completed => [self::Closed],
            self::Closed => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    /**
     * @return array<string,string> value => label, for filter dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}

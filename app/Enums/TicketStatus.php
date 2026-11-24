<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case OnHold = 'on_hold';
    case Solved = 'solved';
    case Closed = 'closed';

    public function label(): string
    {
        return __('enums.statuses.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::OnHold => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Solved => 'bg-teal-50 text-teal-700 ring-teal-200',
            self::Closed => 'bg-slate-200 text-slate-600 ring-slate-300',
        };
    }

    public function isResolved(): bool
    {
        return $this === self::Solved || $this === self::Closed;
    }

    public function isUnresolved(): bool
    {
        return ! $this->isResolved();
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }

    /** @return array<int, string> */
    public static function unresolvedValues(): array
    {
        return collect(self::cases())->filter(fn (self $status) => $status->isUnresolved())->map(fn (self $status) => $status->value)->values()->all();
    }
}

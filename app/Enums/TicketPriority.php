<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __('enums.priorities.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Normal => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::High => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Urgent => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Normal => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $priority) => [$priority->value => $priority->label()])->all();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $priority) => $priority->value, self::cases());
    }
}

<?php

namespace App\Enums;

enum KbStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return __('enums.kb_statuses.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return $this === self::Published
            ? 'bg-teal-50 text-teal-700 ring-teal-200'
            : 'bg-slate-100 text-slate-600 ring-slate-200';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}

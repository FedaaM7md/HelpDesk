<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Agent = 'agent';

    public function label(): string
    {
        return __('enums.roles.'.$this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-brand-50 text-brand-700 ring-brand-200',
            self::Supervisor => 'bg-teal-50 text-teal-700 ring-teal-200',
            self::Agent => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $role) => [$role->value => $role->label()])->all();
    }
}

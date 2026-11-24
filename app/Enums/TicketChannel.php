<?php

namespace App\Enums;

enum TicketChannel: string
{
    case Portal = 'portal';
    case Email = 'email';
    case Agent = 'agent';

    public function label(): string
    {
        return __('enums.channels.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Portal => 'globe',
            self::Email => 'mail',
            self::Agent => 'headset',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $channel) => [$channel->value => $channel->label()])->all();
    }
}

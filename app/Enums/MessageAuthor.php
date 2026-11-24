<?php

namespace App\Enums;

enum MessageAuthor: string
{
    case Requester = 'requester';
    case Agent = 'agent';
    case System = 'system';

    public function label(): string
    {
        return __('enums.authors.'.$this->value);
    }
}

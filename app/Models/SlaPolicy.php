<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = [
        'priority',
        'first_response_minutes',
        'resolution_minutes',
        'use_business_hours',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'first_response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
            'use_business_hours' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function forPriority(TicketPriority $priority): ?self
    {
        return static::query()
            ->where('priority', $priority->value)
            ->where('is_active', true)
            ->first();
    }

    public function firstResponseLabel(): string
    {
        return self::minutesLabel($this->first_response_minutes);
    }

    public function resolutionLabel(): string
    {
        return self::minutesLabel($this->resolution_minutes);
    }

    public static function minutesLabel(int $minutes): string
    {
        if ($minutes % 1440 === 0) {
            return __('common.duration_days', ['count' => intdiv($minutes, 1440)]);
        }

        if ($minutes % 60 === 0) {
            return __('common.duration_hours', ['count' => intdiv($minutes, 60)]);
        }

        return __('common.duration_minutes', ['count' => $minutes]);
    }
}

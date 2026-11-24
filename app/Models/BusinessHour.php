<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    protected $fillable = ['weekday', 'opens_at', 'closes_at', 'is_enabled'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function weekdayName(): string
    {
        return __('common.weekdays.'.$this->weekday);
    }

    public function windowLabel(): string
    {
        if (! $this->is_enabled || ! $this->opens_at || ! $this->closes_at) {
            return __('common.closed');
        }

        return substr($this->opens_at, 0, 5).' – '.substr($this->closes_at, 0, 5);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('weekday');
    }
}

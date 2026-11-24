<?php

namespace App\Services;

use App\Models\BusinessHour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class SlaCalculator
{
    /**
     * Add working minutes to a moment, skipping closed days and hours.
     */
    public function dueAt(CarbonInterface $start, int $minutes, bool $useBusinessHours = true): CarbonImmutable
    {
        $startUtc = CarbonImmutable::instance($start)->utc();

        if (! $useBusinessHours || $minutes <= 0) {
            return $startUtc->addMinutes(max($minutes, 0));
        }

        $timezone = (string) config('helpdesk.timezone', 'UTC');
        $windows = BusinessHour::query()->where('is_enabled', true)->get()->keyBy('weekday');

        if ($windows->isEmpty()) {
            return $startUtc->addMinutes($minutes);
        }

        $cursor = $startUtc->setTimezone($timezone);
        $remaining = $minutes;

        for ($dayOffset = 0; $dayOffset < 400 && $remaining > 0; $dayOffset++) {
            $day = $cursor->startOfDay();
            $window = $windows->get($day->dayOfWeek);

            if ($window && $window->opens_at && $window->closes_at) {
                $opens = $day->setTimeFromTimeString($window->opens_at);
                $closes = $day->setTimeFromTimeString($window->closes_at);

                if ($closes->greaterThan($opens)) {
                    $from = $cursor->greaterThan($opens) ? $cursor : $opens;

                    if ($from->lessThan($closes)) {
                        $available = (int) floor($from->diffInMinutes($closes, true));
                        $consumed = min($available, $remaining);

                        $cursor = $from->addMinutes($consumed);
                        $remaining -= $consumed;

                        if ($remaining === 0) {
                            break;
                        }
                    }
                }
            }

            $cursor = $day->addDay()->startOfDay();
        }

        return $cursor->utc();
    }
}
